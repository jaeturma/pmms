<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\Permission;
use App\Http\Controllers\Concerns\BuildsSchoolOptionsByDelegation;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\AthleteRequest;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AthleteController extends Controller
{
    use BuildsSchoolOptionsByDelegation;
    use SearchesAndPaginates;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Searchable, paginated athlete registry. Officers see only their own
     * delegation's athletes; viewers have no access (minor data).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Athlete::class);

        /** @var User $user */
        $user = $request->user();

        $search = $this->searchTerm($request);

        $query = Athlete::query()
            ->with(['school:id,name', 'delegation.meet:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $query->whereHas('delegation.personnel', fn ($personnel) => $personnel->where('user_id', $user->id));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            $assignments = $user->athleteOversightAssignments()->where('active', true)->get();
            $query->whereHas('school', function ($school) use ($assignments): void {
                $school->where(function ($scope) use ($assignments): void {
                    foreach ($assignments as $assignment) {
                        $scope->orWhere(fn ($item) => $assignment->school_district_id !== null
                            ? $item->where('school_district_id', $assignment->school_district_id)
                            : $item->where('district_id', $assignment->district_id));
                    }
                });
            });
        }

        $this->applySearch($query, $search, ['first_name', 'last_name', 'lrn']);

        $delegations = Delegation::query()
            ->with(['school:id,name', 'district:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegations->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return Inertia::render('athletes/index', [
            'athletes' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'name' => $athlete->fullName(),
                    'sex_label' => $athlete->sex->label(),
                    'age' => $athlete->age(),
                    'grade_level' => $athlete->grade_level,
                    'school' => $athlete->school->name,
                    'meet' => $athlete->delegation->meet->name,
                    'can_update' => $user->can('update', $athlete),
                    'can_delete' => $user->can('delete', $athlete),
                ]),
            'filters' => ['search' => $search],
            'delegationOptions' => $delegations->get()
                ->filter(fn (Delegation $delegation): bool => $user->can('create', [Athlete::class, $delegation]))
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'label' => "{$delegation->registrantName()} — {$delegation->meet->name}",
                ])
                ->values(),
            'schoolOptionsByDelegation' => $this->schoolOptionsByDelegation($delegations->get()),
        ]);
    }

    /**
     * Full athlete profile. Every view of minor data is audited.
     */
    public function show(Athlete $athlete): Response
    {
        Gate::authorize('view', $athlete);

        $this->audit->record('athlete.viewed', $athlete, ['name' => $athlete->fullName()]);

        return Inertia::render('athletes/show', [
            'athlete' => [
                'id' => $athlete->id,
                'first_name' => $athlete->first_name,
                'last_name' => $athlete->last_name,
                'sex' => $athlete->sex->value,
                'sex_label' => $athlete->sex->label(),
                'birthdate' => $athlete->birthdate->toDateString(),
                'age' => $athlete->age(),
                'lrn' => $athlete->lrn,
                'grade_level' => $athlete->grade_level,
                'school' => $athlete->school->name,
                'meet' => $athlete->delegation->meet->name,
                'photo_url' => $athlete->photo_upload_id === null
                    ? null
                    : route('athletes.photo', $athlete),
                'sports_photo_url' => $athlete->sportsPhotoUrl(),
                'can_update' => Gate::allows('update', $athlete),
            ],
        ]);
    }

    /**
     * Serve the athlete's photo, authorized by athlete visibility rather
     * than upload ownership.
     */
    public function photo(Athlete $athlete): HttpResponse
    {
        Gate::authorize('view', $athlete);

        $upload = $athlete->photo;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Serve the athlete's sports/action photo — same authorization as the
     * registry photo above.
     */
    public function sportsPhoto(Athlete $athlete): HttpResponse
    {
        Gate::authorize('view', $athlete);

        $upload = $athlete->sportsPhoto;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Register an athlete under a delegation.
     */
    public function store(AthleteRequest $request): RedirectResponse
    {
        $delegation = Delegation::query()->findOrFail($request->integer('delegation_id'));

        Gate::authorize('create', [Athlete::class, $delegation]);

        $athlete = new Athlete($request->safe()->except(['photo', 'sports_photo']));

        /** @var User $user */
        $user = $request->user();

        if ($request->hasFile('photo')) {
            $athlete->photo_upload_id = $this->uploads->store($request->file('photo'), $user, 'photo')->id;
        }

        if ($request->hasFile('sports_photo')) {
            $athlete->sports_photo_upload_id = $this->uploads->store($request->file('sports_photo'), $user, 'sports_photo')->id;
        }

        $athlete->save();

        $this->audit->record('athlete.created', $athlete, [
            'name' => $athlete->fullName(),
            'school' => $athlete->school->name,
            'registrant' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete registered.')]);

        return back();
    }

    /**
     * Update an athlete, optionally replacing the photo.
     */
    public function update(AthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        Gate::authorize('update', $athlete);

        $athlete->fill($request->safe()->except(['photo', 'sports_photo', 'delegation_id']));

        /** @var User $user */
        $user = $request->user();

        $oldPhoto = null;
        $oldSportsPhoto = null;

        if ($request->hasFile('photo')) {
            $oldPhoto = $athlete->photo;
            $athlete->photo_upload_id = $this->uploads->store($request->file('photo'), $user, 'photo')->id;
        }

        if ($request->hasFile('sports_photo')) {
            $oldSportsPhoto = $athlete->sportsPhoto;
            $athlete->sports_photo_upload_id = $this->uploads->store($request->file('sports_photo'), $user, 'sports_photo')->id;
        }

        $athlete->save();

        if ($oldPhoto !== null) {
            $this->uploads->delete($oldPhoto);
        }

        if ($oldSportsPhoto !== null) {
            $this->uploads->delete($oldSportsPhoto);
        }

        $this->audit->record('athlete.updated', $athlete, ['name' => $athlete->fullName()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete updated.')]);

        return back();
    }

    /**
     * Remove an athlete and their photo.
     */
    public function destroy(Athlete $athlete): RedirectResponse
    {
        Gate::authorize('delete', $athlete);

        $name = $athlete->fullName();
        $photo = $athlete->photo;
        $sportsPhoto = $athlete->sportsPhoto;

        $athlete->delete();

        if ($photo !== null) {
            $this->uploads->delete($photo);
        }

        if ($sportsPhoto !== null) {
            $this->uploads->delete($sportsPhoto);
        }

        $this->audit->record('athlete.deleted', $athlete, ['name' => $name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete removed.')]);

        return redirect()->route('athletes.index');
    }
}
