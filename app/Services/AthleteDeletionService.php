<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\FileUpload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AthleteDeletionService
{
    public function __construct(private readonly AthletePhotoService $athletePhotos) {}

    public function permanentlyDelete(Athlete $athlete): void
    {
        $uploads = $this->uploadsOwnedBy($athlete);

        DB::transaction(function () use ($athlete): void {
            $athlete = Athlete::onlyTrashed()->lockForUpdate()->findOrFail($athlete->id);
            $entryIds = $athlete->entries()->pluck('id');
            $teamEntryIds = $athlete->teamMemberships()->pluck('team_entry_id');

            $hasCompetitionHistory = DB::table('result_placements')
                ->whereIn('entry_id', $entryIds)
                ->orWhereIn('team_entry_id', $teamEntryIds)
                ->exists()
                || DB::table('match_entries')->whereIn('entry_id', $entryIds)->exists()
                || DB::table('match_roster_players')->whereIn('entry_id', $entryIds)->exists();

            if ($hasCompetitionHistory || $athlete->accreditation()->exists()) {
                throw ValidationException::withMessages([
                    'confirm' => __('This athlete cannot be permanently deleted because official competition or accreditation records already exist. Archive the athlete instead.'),
                ]);
            }

            $athlete->sportRosterMemberships()->delete();
            $athlete->teamMemberships()->delete();
            $athlete->entries()->delete();
            $athlete->forceDelete();
        }, 3);

        // Files are removed only after the database commit. A rejected or
        // rolled-back deletion therefore never destroys recoverable files.
        $uploads->each(fn (FileUpload $upload) => $this->athletePhotos->delete($upload));
    }

    /** @return Collection<int, FileUpload> */
    private function uploadsOwnedBy(Athlete $athlete): Collection
    {
        $documentUploadIds = $athlete->eligibilityDocuments()->pluck('file_upload_id');
        $uploadIds = collect([
            $athlete->photo_upload_id,
            $athlete->sports_photo_upload_id,
            ...$documentUploadIds,
        ])->filter()->unique();

        return FileUpload::query()->whereIn('id', $uploadIds)->get();
    }
}
