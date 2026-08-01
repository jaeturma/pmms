<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\DistrictRequest;
use App\Models\District;
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

class DistrictController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Searchable, paginated district registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = District::query()
            ->withCount('schools')
            ->orderBy('name');

        $this->applySearch($query, $search, ['name']);

        return Inertia::render('registry/districts', [
            'districts' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (District $district): array => [
                    'id' => $district->id,
                    'name' => $district->name,
                    'nickname' => $district->nickname,
                    'active' => $district->active,
                    'schools_count' => $district->schools_count,
                    'logo_url' => $district->logoUrl(),
                ]),
            'filters' => ['search' => $search],
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Serve the district's (municipality's) logo. Public — crests are shown
     * on the guest-facing portal, not sensitive data like an athlete photo.
     */
    public function logo(District $district): HttpResponse
    {
        $upload = $district->logo;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Create a district.
     */
    public function store(DistrictRequest $request): RedirectResponse
    {
        $district = new District($request->safe()->except(['logo', 'remove_logo']));

        if ($request->hasFile('logo')) {
            /** @var User $user */
            $user = $request->user();
            $district->logo_upload_id = $this->uploads->store($request->file('logo'), $user, 'logo')->id;
        }

        $district->save();

        $this->audit->record('district.created', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District created.')]);

        return back();
    }

    /**
     * Update a district, optionally replacing or removing its logo.
     */
    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        $district->fill($request->safe()->except(['logo', 'remove_logo']));

        $oldLogo = null;

        if ($request->hasFile('logo')) {
            /** @var User $user */
            $user = $request->user();
            $oldLogo = $district->logo;
            $district->logo_upload_id = $this->uploads->store($request->file('logo'), $user, 'logo')->id;
        } elseif ($request->boolean('remove_logo') && $district->logo_upload_id !== null) {
            $oldLogo = $district->logo;
            $district->logo_upload_id = null;
        }

        $district->save();

        if ($oldLogo !== null) {
            $this->uploads->delete($oldLogo);
        }

        $this->audit->record('district.updated', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District updated.')]);

        return back();
    }

    /**
     * Archive a district instead of deleting it.
     */
    public function archive(District $district): RedirectResponse
    {
        $district->forceFill(['active' => false])->save();

        $this->audit->record('district.archived', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District archived.')]);

        return back();
    }

    /**
     * Restore an archived district.
     */
    public function restore(District $district): RedirectResponse
    {
        $district->forceFill(['active' => true])->save();

        $this->audit->record('district.restored', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District restored.')]);

        return back();
    }

    /**
     * Delete a district that no school references.
     */
    public function destroy(District $district): RedirectResponse
    {
        if ($district->schools()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This district has schools. Archive it instead.'),
            ]);

            return back();
        }

        $logo = $district->logo;

        $district->delete();

        if ($logo !== null) {
            $this->uploads->delete($logo);
        }

        $this->audit->record('district.deleted', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District deleted.')]);

        return back();
    }
}
