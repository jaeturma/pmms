<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Personnel;
use App\Services\AthletePhotoService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'canUpdatePhoto' => $this->canUpdatePhoto($request),
            'photoUrl' => $request->user()->profile_photo_upload_id === null ? null : route('profile.photo'),
        ]);
    }

    public function updatePhoto(Request $request, AthletePhotoService $photos): RedirectResponse
    {
        abort_unless($this->canUpdatePhoto($request), 403);
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('pmms.athlete_photos.max_upload_kb')],
        ]);
        $user = $request->user();
        $oldUploads = collect([$user->profilePhoto]);
        $upload = $photos->store($validated['photo'], $user, 'passport');

        DB::transaction(function () use ($user, $upload, $oldUploads): void {
            $user->forceFill(['profile_photo_upload_id' => $upload->id])->save();

            if ($user->hasRole(UserRole::Coach)) {
                $onboarding = CoachOnboardingRequest::query()->where('user_id', $user->id)->latest('id')->first();
                $personnel = Personnel::query()->where('user_id', $user->id)->with('photo')->get();
                $oldUploads->push($onboarding?->profile)->push(...$personnel->pluck('photo'));
                $onboarding?->forceFill(['profile_upload_id' => $upload->id])->save();
                Personnel::query()->where('user_id', $user->id)->update(['photo_upload_id' => $upload->id]);
            }
        });

        $oldUploads->filter()->unique('id')->each(function ($old) use ($photos, $upload): void {
            if ($old->id !== $upload->id) {
                $photos->delete($old);
            }
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo updated.')]);

        return back();
    }

    public function photo(Request $request): HttpResponse
    {
        abort_unless($this->canUpdatePhoto($request), 403);
        $upload = $request->user()->profilePhoto;
        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    private function canUpdatePhoto(Request $request): bool
    {
        $user = $request->user();

        return $user->hasRole(UserRole::Coach, UserRole::TournamentICT)
            || $user->canManageProductionAccounts();
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
