<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\PersonnelRequest;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PersonnelController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Searchable, paginated personnel registry with athlete-style scoping.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Personnel::class);

        /** @var User $user */
        $user = $request->user();

        $search = trim((string) $request->query('search', ''));

        $query = Personnel::query()
            ->with(['delegation.school:id,name', 'delegation.meet:id,name', 'sports:id,name'])
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
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $delegations = Delegation::query()->with(['school:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegations->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return Inertia::render('personnel/index', [
            'personnel' => $query->paginate(15)->withQueryString()
                ->through(fn (Personnel $person): array => [
                    'id' => $person->id,
                    'first_name' => $person->first_name,
                    'last_name' => $person->last_name,
                    'name' => $person->fullName(),
                    'role' => $person->role->value,
                    'role_label' => $person->role->label(),
                    'coaches' => $person->role->coaches(),
                    'phone' => $person->phone,
                    'email' => $person->email,
                    'sports' => $person->sports->pluck('name')->all(),
                    'sport_ids' => $person->sports->pluck('id')->all(),
                    'school' => $person->delegation->school->name,
                    'meet' => $person->delegation->meet->name,
                    'photo_url' => $person->photo_upload_id === null
                        ? null
                        : route('personnel.photo', $person),
                    'can_update' => $user->can('update', $person),
                    'can_delete' => $user->can('delete', $person),
                ]),
            'filters' => ['search' => $search],
            'delegationOptions' => $delegations->get()
                ->filter(fn (Delegation $delegation): bool => $user->can('create', [Personnel::class, $delegation]))
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'label' => "{$delegation->school->name} — {$delegation->meet->name}",
                ])
                ->values(),
            'sportOptions' => Sport::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Serve the person's photo, authorized by record visibility.
     */
    public function photo(Personnel $personnel): HttpResponse
    {
        Gate::authorize('view', $personnel);

        $upload = $personnel->photo;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Register a coach, assistant coach, or chaperone under a delegation.
     */
    public function store(PersonnelRequest $request): RedirectResponse
    {
        $delegation = Delegation::query()->findOrFail($request->integer('delegation_id'));

        Gate::authorize('create', [Personnel::class, $delegation]);

        $person = new Personnel($request->safe()->except(['photo']));

        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            /** @var User $user */
            $user = $request->user();
            $person->photo_upload_id = $this->uploads->store($photo, $user)->id;
        }

        $person->save();

        $this->audit->record('personnel.created', $person, [
            'name' => $person->fullName(),
            'role' => $person->role->value,
            'school' => $delegation->school->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel registered.')]);

        return back();
    }

    /**
     * Update a person, optionally replacing the photo. Sport assignments
     * are cleared when the role loses its coaching nature.
     */
    public function update(PersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        Gate::authorize('update', $personnel);

        $personnel->fill($request->safe()->except(['photo', 'delegation_id']));

        $oldPhoto = null;
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            /** @var User $user */
            $user = $request->user();
            $oldPhoto = $personnel->photo;
            $personnel->photo_upload_id = $this->uploads->store($photo, $user)->id;
        }

        $personnel->save();

        if (! $personnel->role->coaches()) {
            $personnel->sports()->detach();
        }

        if ($oldPhoto !== null) {
            $this->uploads->delete($oldPhoto);
        }

        $this->audit->record('personnel.updated', $personnel, ['name' => $personnel->fullName()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel updated.')]);

        return back();
    }

    /**
     * Replace a coach's sport assignments.
     */
    public function syncSports(Request $request, Personnel $personnel): RedirectResponse
    {
        Gate::authorize('update', $personnel);

        if (! $personnel->role->coaches()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only coaches can be assigned sports.'),
            ]);

            return back();
        }

        $validated = $request->validate([
            'sport_ids' => ['array'],
            'sport_ids.*' => ['integer', Rule::exists('sports', 'id')],
        ]);

        /** @var array<int, int> $sportIds */
        $sportIds = $validated['sport_ids'] ?? [];

        $personnel->sports()->sync($sportIds);

        $this->audit->record('personnel.sports_updated', $personnel, [
            'name' => $personnel->fullName(),
            'sport_count' => count($sportIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport assignments updated.')]);

        return back();
    }

    /**
     * Remove a person and their photo.
     */
    public function destroy(Personnel $personnel): RedirectResponse
    {
        Gate::authorize('delete', $personnel);

        $name = $personnel->fullName();
        $photo = $personnel->photo;

        $personnel->delete();

        if ($photo !== null) {
            $this->uploads->delete($photo);
        }

        $this->audit->record('personnel.deleted', $personnel, ['name' => $name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel removed.')]);

        return back();
    }
}
