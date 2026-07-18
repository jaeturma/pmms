# Athlete Registry

WP-02-06. Athletes are minors — this module collects the minimum, restricts access
hardest, and audits every profile view.

## Data model

`athletes` — `delegation_id` (FK restrict; delegations with athletes cannot be deleted),
`first_name`/`last_name`, `sex` (`App\Enums\Sex`), `birthdate` (sanity: age 5–25),
`lrn` (12 digits, unique), `grade_level` (1–12), optional `photo_upload_id` referencing
`file_uploads` (photo stored via the existing `FileUploadService`, replaced/cleaned up on
update/delete). **No** medical, address, or guardian data — deliberately out of scope.

## Authorization (AthletePolicy)

- **Viewers have no access at all** (viewAny denies — minor data is not a
  "non-sensitive list").
- Officers see and manage only their own delegation's athletes, and only while the
  delegation is an editable draft with registration open
  (`Delegation::isEditableByOfficers()`).
- Admins/organizers manage all, at any time.
- The photo is served through `GET athletes/{athlete}/photo`, authorized by **athlete**
  visibility (not upload ownership), so an officer sees their athletes' photos but
  nobody else's.

## Audit

`athlete.created|updated|deleted` and — because this is minor data — **every profile
view** (`athlete.viewed`). Photo storage itself additionally logs `file.uploaded`/
`file.deleted` via the upload service.

## UI

- `athletes/index.tsx` — first searchable, paginated registry (server-side `search`
  across names and LRN, 15 per page). LRN and birthdate are deliberately **not** shown
  in the list; they appear only on the audited profile page.
- `athletes/show.tsx` — full profile with photo.
- Registration dialog offers only delegations the current user may add athletes to
  (policy-filtered server-side).

WP-02-10 will generalize the search/pagination pattern to the other registries.
