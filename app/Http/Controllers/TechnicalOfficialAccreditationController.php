<?php

namespace App\Http\Controllers;

use App\Enums\RequirementStatus;
use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\TechnicalOfficialAccreditation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class TechnicalOfficialAccreditationController extends Controller
{
    public function __construct(private readonly FileUploadService $uploads, private readonly AuditLogger $audit) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')], 'sport_id' => ['required', Rule::exists('sports', 'id')],
            'accreditation_type' => ['required', 'string', 'max:100'], 'certificate_number' => ['nullable', 'string', 'max:100'],
            'issuing_organization' => ['nullable', 'string', 'max:160'], 'level' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $official = User::query()->findOrFail($data['user_id']);
        abort_unless($request->user()?->hasRole(UserRole::Admin, UserRole::Organizer) || $request->user()?->is($official), 403);
        abort_unless($official->role === UserRole::TechnicalOfficial, 422);
        $upload = $this->uploads->store($request->file('file'), $request->user());
        $credential = TechnicalOfficialAccreditation::query()->create([...collect($data)->except('file')->all(), 'file_upload_id' => $upload->id, 'status' => RequirementStatus::Submitted]);
        $this->audit->record('technical_official.accreditation.uploaded', $credential, ['sport' => Sport::query()->find($data['sport_id'])?->name]);

        return back();
    }

    public function updateStatus(Request $request, TechnicalOfficialAccreditation $accreditation): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(UserRole::Admin, UserRole::Organizer), 403);
        $data = $request->validate(['status' => ['required', Rule::in([RequirementStatus::Verified->value, RequirementStatus::Rejected->value])], 'remarks' => ['nullable', 'string', 'max:1000']]);
        $accreditation->forceFill(['status' => $data['status'], 'verified_by' => $request->user()?->id, 'verified_at' => now(), 'remarks' => $data['remarks'] ?? null])->save();
        $this->audit->record('technical_official.accreditation.'.$data['status'], $accreditation);

        return back();
    }

    public function download(Request $request, TechnicalOfficialAccreditation $accreditation): Response
    {
        abort_unless($request->user()?->hasRole(UserRole::Admin, UserRole::Organizer) || $request->user()?->is($accreditation->user), 403);
        $upload = $accreditation->fileUpload;
        $this->audit->record('technical_official.accreditation.viewed', $accreditation);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }
}
