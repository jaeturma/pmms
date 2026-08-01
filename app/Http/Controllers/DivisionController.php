<?php

namespace App\Http\Controllers;

use App\Enums\DivisionType;
use App\Http\Requests\DivisionRequest;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The division's settings: city or province type, and the label that
 * follows from it ("District" vs "Municipality") — see
 * App\Enums\DivisionType and docs/division.md.
 */
class DivisionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Show the division settings. Admin-only (`can:administer` route
     * middleware) — the current division is already shared to every page
     * via Inertia for read purposes.
     */
    public function edit(): Response
    {
        $division = Division::current();

        return Inertia::render('division/edit', [
            'division' => [
                'type' => $division->type->value,
                'name' => $division->name,
                'areaLabel' => $division->areaLabel(),
                'logo_url' => $division->logo_upload_id === null ? null : route('division.logo'),
            ],
            'typeLocked' => $division->typeIsLocked(),
        ]);
    }

    /**
     * Serve the division's site-wide brand logo. Public — it's shown in the
     * guest-facing portal header, not sensitive data.
     */
    public function logo(): HttpResponse
    {
        $upload = Division::current()->logo;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Update the division settings. The type is refused once any
     * delegation exists — see Division::typeIsLocked(). The logo may be
     * replaced or, via `remove_logo`, cleared back to the placeholder mark.
     */
    public function update(DivisionRequest $request): RedirectResponse
    {
        $division = Division::current();
        $validated = $request->validated();

        $from = $division->type;

        $division->forceFill([
            'name' => $validated['name'],
            'type' => isset($validated['type']) ? DivisionType::from($validated['type']) : $division->type,
        ]);

        $oldLogo = null;

        if ($request->hasFile('logo')) {
            /** @var User $user */
            $user = $request->user();
            $oldLogo = $division->logo;
            $division->logo_upload_id = $this->uploads->store($request->file('logo'), $user, 'logo')->id;
        } elseif ($request->boolean('remove_logo') && $division->logo_upload_id !== null) {
            $oldLogo = $division->logo;
            $division->logo_upload_id = null;
        }

        $division->save();

        if ($oldLogo !== null) {
            $this->uploads->delete($oldLogo);
        }

        $this->audit->record('division.updated', $division, [
            'name' => $division->name,
            'type' => ['from' => $from->value, 'to' => $division->type->value],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Division settings updated.')]);

        return back();
    }
}
