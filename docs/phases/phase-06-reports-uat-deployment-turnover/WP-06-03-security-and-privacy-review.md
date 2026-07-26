# WP-06-03 — Security & Privacy Review

## Purpose
A closure pass on security and privacy before the app is handed off for real use
— re-verify existing controls still hold after every phase since the last review
(Phase 7's live scoring, its public exposure in WP-07-08, and WP-06-02's new
backup artifact), and specifically review minor-athlete/PII exposure paths. This
builds on real existing foundations (`AuthorizationMatrixTest`, `AuditLogger`,
clean `composer audit`/`npm audit` as of Phase 7) rather than starting from zero.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Re-run `composer audit` and `npm audit` (`--omit=dev` for npm) and resolve
  anything newly flagged since Phase 7's last clean run.
- Re-verify `AuthorizationMatrixTest` coverage against `docs/authorization.md`'s
  matrix is still exhaustive — specifically confirm WP-07-08's new public
  scoreboard routes are covered by the existing "Public portal" row (or need
  their own), and that WP-06-02's backup artifact location isn't reachable via
  any web route.
- Review minor-athlete and guardian PII exposure paths specifically: which
  fields appear on which report/CSV/public page, cross-checked against
  `docs/reports.md` and `docs/public-portal.md`'s stated "name+school+placement
  only" public identity rule — confirm no report or public page leaks more than
  documented (e.g., date of birth, contact info, medical/eligibility data).
- Review file uploads (`FileUploadService` + policy, `docs/file-uploads.md`)
  for anything that's drifted since it was last reviewed.
- Review session/CSRF/auth posture (Fortify config, `config/session.php`) for
  anything that needs a production-appropriate change (this may overlap with
  WP-06-07's `.env` hardening — note findings there rather than duplicating the
  fix).
- Produce a findings report in the same format as the Phase 3/7 compliance
  reviews (COMPLIANT / findings list with severity), filed as
  `docs/phases/phase-06-reports-uat-deployment-turnover/phase-6-security-review.md`.
  Fix any findings found (same convention as Phase 7's review, which fixed two
  Low accessibility gaps inline).

## Out of Scope
A full penetration test or external audit; rearchitecting authorization; new
security features beyond closing findings from this review.

## Deliverables
- `phase-6-security-review.md`
- Any fixes for findings surfaced during the review
- Updated documentation where a finding requires a doc correction
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- `composer audit`/`npm audit` clean or findings resolved.
- Tests and quality checks completed.
- Documentation updated.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-06-04 — Performance & Load Verification
