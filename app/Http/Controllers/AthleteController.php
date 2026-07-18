<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\AthleteRequest;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AthleteController extends Controller
{
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

        $search = trim((string) $request->query('search', ''));

        $query = Athlete::query()
            ->with(['delegation.school:id,name', 'delegation.meet:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('lrn', 'like', "%{$search}%");
            });
        }

        $delegations = Delegation::query()
            ->with(['school:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegations->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return Inertia::render('athletes/index', [
            'athletes' => $query->paginate(15)->withQueryString()
                ->through(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'name' => $athlete->fullName(),
                    'sex_label' => $athlete->sex->label(),
                    'age' => $athlete->age(),
                    'grade_level' => $athlete->grade_level,
                    'school' => $athlete->delegation->school->name,
                    'meet' => $athlete->delegation->meet->name,
                    'can_update' => $user->can('update', $athlete),
                    'can_delete' => $user->can('delete', $athlete),
                ]),
            'filters' => ['search' => $search],
            'delegationOptions' => $delegations->get()
                ->filter(fn (Delegation $delegation): bool => $user->can('create', [Athlete::class, $delegation]))
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'label' => "{$delegation->school->name} — {$delegation->meet->name}",
                ])
                ->values(),
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
                'school' => $athlete->delegation->school->name,
                'meet' => $athlete->delegation->meet->name,
                'photo_url' => $athlete->photo_upload_id === null
                    ? null
                    : route('athletes.photo', $athlete),
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
     * Register an athlete under a delegation.
     */
    public function store(AthleteRequest $request): RedirectResponse
    {
        $delegation = Delegation::query()->findOrFail($request->integer('delegation_id'));

        Gate::authorize('create', [Athlete::class, $delegation]);

        $athlete = new Athlete($request->safe()->except(['photo']));

        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            /** @var User $user */
            $user = $request->user();
            $athlete->photo_upload_id = $this->uploads->store($photo, $user)->id;
        }

        $athlete->save();

        $this->audit->record('athlete.created', $athlete, [
            'name' => $athlete->fullName(),
            'school' => $delegation->school->name,
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

        $athlete->fill($request->safe()->except(['photo', 'delegation_id']));

        $oldPhoto = null;
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            /** @var User $user */
            $user = $request->user();
            $oldPhoto = $athlete->photo;
            $athlete->photo_upload_id = $this->uploads->store($photo, $user)->id;
        }

        $athlete->save();

        if ($oldPhoto !== null) {
            $this->uploads->delete($oldPhoto);
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

        $athlete->delete();

        if ($photo !== null) {
            $this->uploads->delete($photo);
        }

        $this->audit->record('athlete.deleted', $athlete, ['name' => $name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete removed.')]);

        return redirect()->route('athletes.index');
    }
}
