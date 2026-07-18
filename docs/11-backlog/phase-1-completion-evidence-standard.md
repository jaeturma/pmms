# PMMS Phase 1 Completion Evidence Standard

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-definition-of-done.md](phase-1-definition-of-done.md)

Defines the evidence format every work package's Section 27 (Completion Evidence) must use once it is actually executed. **No actual results are included in Phase 0.14** — this document defines the standard only; every work package template's Section 27 in this backlog is populated with placeholders, not real output, per the originating prompt's instruction.

## 1. Required Evidence Categories

1. **Commands run** — the exact CLI commands executed (e.g., `php artisan test`, `npm run lint:check`), verbatim.
2. **Test results** — pass/fail counts and, for any failure encountered and then fixed, a brief note of what changed.
3. **Static-analysis results** — Larastan/PHPStan, ESLint, `tsc --noEmit`, and (from EPIC-12 onward) `flutter analyze` output summaries.
4. **Screenshots** — required for any EPIC-11/EPIC-12 UI work package, showing the implemented state in both light and dark theme where applicable.
5. **API response examples** — required for any EPIC-10 work package, showing at least one success and one error-contract example.
6. **Migration status** — `php artisan migrate:status` output, or equivalent, for any work package with Section 9 ≠ None.
7. **Audit examples** — a representative recorded audit/activity-history/security event, with sensitive fields redacted from the evidence itself.
8. **Accessibility evidence** — automated-tool output plus a manual keyboard-navigation note, for EPIC-11/EPIC-12 UI work packages.
9. **Before-and-after notes** — a short summary of what existed before the work package (from Section 7, Current-State Inspection) versus after.
10. **Git diff summary** — file-count and line-count summary of the change, confirming no unrelated files are included.
11. **Files created** — explicit list.
12. **Files modified** — explicit list.
13. **Known limitations** — anything the work package's own Definition of Done could not fully satisfy, with a stated reason and, if applicable, a follow-up decision or risk ID.

## 2. Evidence Placement

Evidence is attached to the work package's completion report (delivered per its Section 31 execution-prompt structure) and, where the work package's Section 22 requires it, mirrored into the relevant `docs/` or `.ai/` documentation update.

## 3. Evidence Discipline

- Evidence must be genuine output from an actual run, never fabricated or inferred. A work package cannot be marked Implementation Complete on the basis of "this should work" reasoning alone.
- Evidence showing a failing check does not itself block marking a work package Implementation Complete if the failure is later resolved and re-evidenced — but the completion report must show the resolution, not just the final passing state, so reviewers can see what was fixed.
- Sensitive data (real participant, medical, or credential data) must never appear in evidence — use synthetic or anonymized examples only, consistent with EPIC-14's data-protection foundation.
