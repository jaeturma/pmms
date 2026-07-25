# Athlete Registry

WP-02-06. Athletes are minors — this module collects the minimum, restricts access
hardest, and audits every profile view.

## Data model

`athletes` — `delegation_id` (FK restrict; delegations with athletes cannot be deleted),
`school_id` (FK restrict — the athlete's own home school, set once at registration and
never changed afterward; see "Home school" below), `first_name`/`last_name`, `sex`
(`App\Enums\Sex`), `birthdate` (sanity: age 5–25), `lrn` (12 digits, unique),
`grade_level` (1–12), optional `photo_upload_id` referencing `file_uploads` (photo stored
via the existing `FileUploadService`, replaced/cleaned up on update/delete). **No**
medical, address, or guardian data — deliberately out of scope.

## Home school (Division initiative)

An athlete's `school_id` is decoupled from their delegation's registering unit — a
municipal (Province) delegation pools multiple schools, so "which delegation
registered them" no longer answers "which school are they from." At registration,
the school picker is constrained server-side (`AthleteRequest::withValidator()`) to
either the delegation's own school (City) or any active school within the
delegation's municipality (Province) — never a school outside where the delegation
actually registered. Required on create, immutable after — the same pattern as
`delegation_id`. `Athlete::school()` is the only correct source for an individual
athlete's school; never infer it from the delegation (see `docs/delegations.md`
"Individual attribution is fully re-keyed" — every module reads it this way).

## Authorization (AthletePolicy)

- **Viewers have no access at all** (viewAny denies — minor data is not a
  "non-sensitive list").
- Officers see and manage only their own delegation's athletes, and only while the
  delegation is an editable draft with registration open
  (`Delegation::isEditableByOfficers()`). Scoping is by **delegation**, not by an
  athlete's own `school_id` — an officer assigned to a municipal (Province)
  delegation therefore sees the delegation's full pooled roster across every
  school it registers under, not just their own school. Accepted and intended,
  not a gap: see `docs/delegations.md` "Officer roster scope" for why.
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
  (policy-filtered server-side); once a delegation is picked, the school field narrows
  to the schools valid for it (auto-selected when there's only one, i.e. always for a
  City delegation).

WP-02-10 will generalize the search/pagination pattern to the other registries.
