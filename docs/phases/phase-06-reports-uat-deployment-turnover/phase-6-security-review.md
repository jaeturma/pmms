# Phase 6 — Security & Privacy Review (WP-06-03)

**Reviewed:** 2026-07-26 · **Scope:** re-verification of existing controls after
every phase since the last review (Division initiative, Phase 5, Phase 7
incl. its public live scoreboard), plus a dedicated minor-athlete/guardian
PII exposure sweep · **Result: COMPLIANT** (no Critical/High findings; one
Medium finding — no rate limit on self-registration — found and fixed
during this review; see §6)

This is a closure pass, not a from-zero audit: it re-verifies real existing
foundations (`AuthorizationMatrixTest`, `AuditLogger`, `FileUploadService` +
policy, Fortify) rather than re-deriving them, per the WP's own scope note.

## 1. Dependency Audit

- `composer audit`: **No security vulnerability advisories found.**
- `npm audit --omit=dev`: **found 0 vulnerabilities.**
- Both clean since Phase 7's own audit (WP-07-01 fixed the one guzzlehttp
  advisory found there); no new dependency has been added since.

## 2. Authorization Matrix Re-Verification

Walked `docs/authorization.md`'s matrix against the current codebase, with
two specific things this WP was asked to confirm:

- **WP-07-08's public live scoreboard routes** (`public.scoreboard`,
  `public.scoreboard.poll`) — covered by the existing "Public portal (`/`)"
  matrix row, not a separate row, consistent with how every other public
  route (home, meet, results, tally) is already documented: individual
  public pages never get their own matrix rows, only the one blanket "guest,
  no authentication, published meets only" row. `PublicScoreboardTest`
  (5 tests, part of the 650 passing below) independently proves guests can
  view it, unpublished meets 404, a match outside the given meet 404s, and
  operator-only fields (`canManage`, `suggestedLabels`) are structurally
  absent from the page's own props — the same bar every other public page
  test uses.
- **WP-06-02's backup artifact location** — `storage/app/private/backups/
  database/` sits on the `local` filesystem disk, whose root is
  `storage_path('app/private')` (`config/filesystems.php`); only
  `storage_path('app/public')` is symlinked to `public/storage` by
  `php artisan storage:link`. Confirmed no web route resolves anywhere under
  `storage/app/private/` — the backup files are unreachable via any URL, as
  WP-06-02 already documented.
- Every other row spot-checked against the actual policy/controller/route
  middleware it claims (`role:admin,organizer` groups in `routes/web.php`,
  `DelegationPolicy`/`AthletePolicy`/`PersonnelPolicy`/`EligibilityReviewPolicy`/
  `ProtestPolicy`/`FileUploadPolicy`) — all still match; no drift found since
  the Division initiative's WP7 review and Phase 7's WP-07-03 review.
- `role` remains deliberately non-mass-assignable
  (`User`'s `#[Fillable(['name', 'email', 'password'])]` attribute excludes
  it) — re-confirmed by direct inspection, not just the doc's claim.

## 3. Minor-Athlete & Guardian PII Exposure Sweep

Cross-checked every report/CSV/public page against `docs/reports.md`'s
inventory and `docs/public-portal.md`'s binding "name + school +
placement/mark only" public rule.

**Public surfaces (`PortalController`) — compliant.** Read the full
controller: `results()` exposes only `athlete->fullName()`, `school->name`,
`mark`, `rank`, `is_tie`; `tally()` delegates to `MedalTallyService`
unchanged; `scoreboard()`/`scoreboardPoll()` expose only the operator's own
free-text side labels and a running score (no athlete fields at all, per
`docs/live-scoring.md` and confirmed by inspection — a scoring session
carries no athlete/personnel columns anywhere in its schema); `meet()`'s
venue guide exposes only venue name + address, never internal venue notes.
No birthdate, LRN, grade level, contact detail, guardian information, or
eligibility material appears on any public route — matches
`docs/public-portal.md`'s "Never public" list exactly.

**Internal reports — compliant, correctly scoped to managers/officers.**
The delegation roster report (`ReportController::rosterData()`) does include
birthdate, LRN, and grade level in its CSV — this is expected and
documented (`docs/reports.md`), and the route is restricted to managers and
the delegation's own assigned officer (`DelegationPolicy::viewRoster`),
never a Viewer, matching the matrix's "own only / ✗ Viewer" row. No report
was found exposing more than `docs/reports.md` documents; all six reports'
field lists match what's actually queried and rendered.

## 4. File Upload Review

- `FileUploadService::store()`: extension allow-list (`jpg, jpeg, png, webp,
  pdf, doc, docx, xls, xlsx`) + 10 MB cap enforced via `FileUploadRequest`'s
  `File::types()` rule (validates the actual detected file type, not just
  the client-supplied name); stored under framework-generated hashed
  filenames on the private `local` disk; `mime_type` recorded from
  `UploadedFile::getMimeType()` (server-side content sniffing), never a
  client-supplied header.
- `FileUploadController::download()` uses `Storage::disk()->download()`,
  which forces `Content-Disposition: attachment` — safe against inline
  SVG/HTML content-type confusion even if a stored file's real type were
  ever misidentified.
- `FileUploadPolicy` is uploader-only, as documented — this is the correct
  scope for the generic building block. Eligibility documents (the one
  minor-PII-adjacent use of file uploads) correctly do **not** route through
  this generic owner-only gate: `EligibilityController::downloadDocument()`
  authorizes against `EligibilityReviewPolicy::view` instead (managers see
  any review, an officer only their own delegation's), so a manager can
  review a document they didn't personally upload — verified this is a
  deliberate, correctly-implemented separation, not a policy contradiction.
  Every document view is audited (`eligibility.document_viewed`).
- `EligibilityController::downloadDocument()` serves inline
  (`Storage::disk()->response()`) rather than as an attachment, unlike the
  generic endpoint — acceptable here because the upload type allow-list for
  this path is far narrower (`mimes:pdf,jpg,jpeg,png` only, enforced by
  Laravel's `mimes` rule against the actual detected content, not the
  filename), so an HTML/SVG substitution that would make inline serving
  dangerous is already rejected at upload time.
- No drift found since the file-upload foundation was last reviewed.

## 5. Session, CSRF & Auth Posture

- CSRF: no exceptions registered anywhere (`bootstrap/app.php` adds no
  `validateCsrfTokens(except: …)`) — the framework default `web` group
  protection (via Laravel's standard CSRF middleware) covers every route
  used by this app; there is no API surface (`routes/api.php` doesn't
  exist) and no CORS config, so there's nothing to review there beyond
  confirming both are genuinely absent, not overlooked.
- `config/session.php` is framework-default: `http_only` true, `same_site`
  lax, database driver, 120-minute lifetime. `secure` (HTTPS-only cookies)
  and `encrypt` both read from `.env` and default to off/false when unset —
  this is a **production `.env` value**, not application code, so per this
  WP's own scope note it's recorded here and left for WP-06-07's `.env`
  hardening pass rather than duplicated: **production must set
  `SESSION_SECURE_COOKIE=true`** once the deployment serves over HTTPS.
- Fortify: login/two-factor/passkeys all correctly rate-limited (5/min,
  5/min, 10/min respectively, all IP- or session-scoped) via
  `FortifyServiceProvider::configureRateLimiting()`. Email verification
  (`Features::emailVerification()`) is enabled and required before a
  self-registered account can do anything beyond viewing its own profile.
  2FA and passkeys are both available and correctly gated by
  `confirmPassword`.

## 6. Finding: Registration Had No Rate Limit (Medium — fixed this WP)

**Finding.** `POST /register` (Fortify's self-registration endpoint) had no
rate limiter at all — unlike every other Fortify auth action. Verified by
inspecting `vendor/laravel/fortify/src/routes/routes.php`: login gets
`throttle:login`, email verification gets `throttle:6,1`, but the
registration routes carry only `['guest:web']`, and Fortify has no
`limiters.registration` config hook to attach one declaratively (its
`limiters` config array only recognizes `login`, `two-factor`, `passkeys`).
This allowed unlimited automated account-creation attempts and, since every
attempt emails a verification link to whatever address is submitted,
unlimited mail-bombing of arbitrary third-party addresses regardless of
whether the attempt ever completes.

**Severity reasoning.** Not High: `role` is not mass-assignable
(`CreateNewUser` only ever sets `name`/`email`/`password`), so an attacker
can never self-register anything above the `viewer` default — no path to
Organizer/Admin/Delegation-Officer data. Not Low: `viewer` already grants
authenticated access to delegation lists, schedules, non-sensitive reports,
and medal tally (per the matrix), so unlimited free account creation is a
real internal-data-exposure and abuse vector (spam accounts, mail-bombing),
not merely cosmetic.

**Fix.** `App\Http\Middleware\ThrottleRegistration`, appended to the global
`web` middleware group in `bootstrap/app.php`. It's a no-op for every route
except `register.store` (checked via `$request->routeIs()`, evaluated after
routing so this is unambiguous regardless of Fortify's own route
registration timing), where it enforces the same 5-per-minute-per-IP bar
`login` already uses via `RateLimiter::tooManyAttempts()`/`hit()` directly
(a plain IP key, not a named Fortify limiter — Fortify's route is
registered entirely inside the package with no hook to attach a named
limiter to it declaratively, so this enforces the same limit outside that
mechanism instead of fighting it). New test:
`'registration is rate limited (WP-06-03)'` in
`tests/Feature/Auth/RegistrationTest.php`, mirroring the existing
`AuthenticationTest`'s login-throttle test shape.

No other findings — self-registration's *outcome* (a `viewer`-only account
requiring real email-verification) was already sound; only the missing
rate limit needed closing.

## 7. Quality Gate (this WP)

- Pint: **PASS** (clean, full repo) · PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — **650 tests / 3,245 assertions, 0 failures** (649 at
  WP-06-02 close → +1 this WP, the registration-throttle proof in §6)
- ESLint: **PASS** · Prettier (`npm run format:check`): **PASS** · tsc
  strict: **PASS**
- `composer audit`: **0 advisories** · `npm audit --omit=dev`:
  **0 vulnerabilities**
- No schema changes — nothing to migrate.

## 8. Findings and Dispositions

1. **No Critical/High findings.**
2. **One Medium finding, fixed during this review** (§6) — self-registration
   had no rate limit; `ThrottleRegistration` middleware added, proven by a
   new test. No further action.
3. **Carried forward for WP-06-07** (`.env` production hardening, not
   duplicated here per this WP's own scope note): set
   `SESSION_SECURE_COOKIE=true` once the deployment serves over HTTPS (§5);
   the previously-known queue-worker gap for `ScoreUpdated` broadcasts
   (`.ai/current-phase.md`, unrelated to this review) remains WP-06-07's to
   close.
4. **Not committed/pushed**, per project rules — the tree is green pending
   owner instruction.

## 9. Recommendation

Phase 6's security posture re-verification found the app's existing
controls (authorization matrix, audit trail, file-upload policy, minor-PII
handling on every public and internal surface) all still hold after the
Division initiative and Phase 7, including its public live-scoreboard
expansion — no drift, no undocumented exposure. The one real gap found —
unthrottled self-registration — is now closed and proven by test.
Recommended next: **WP-06-04 — Performance & Load Verification**.
