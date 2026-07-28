# WP-09-01 — Completion Report

Bug & Support Workflow. Status: **done**.

## Repository findings

Before writing anything, inspected: `.github/` did not exist in this repo
(confirmed — Phase 1 removed the starter kit's own copy early on, per
this WP's own README grounding), so this is a genuinely new addition, not
a restore. `docs/turnover.md`'s escalation table had five `_[fill in]_`
placeholders in its "How" column, entirely disconnected from any real
workflow. `docs/manuals/admin-manual.md` §2 documents the real,
current role-promotion limitation (`php artisan tinker` /
`$user->forceFill(['role' => …])->save()`) that the Support request
template's own example references — confirmed the exact wording rather
than guessing. `app/Enums/UserRole.php` gave the four real role labels
(Administrator, Meet Organizer, Delegation Officer, Viewer) used in the
Bug report template's "which role" dropdown, plus a fifth option for the
unauthenticated public portal.

**A real, unrelated numbering drift was found and fixed first**: this
entire directory's own prose (`README.md`, `CHECKLIST.md`,
`DESIGN-NOTES.md`, and all three `WP-09-0X-*.md` files) still called
itself "Phase 8"/`WP-08-0X` throughout — a leftover from before Phase 8
(UI/UX) and Phase 8.5 (Premium Sports Experience) were both inserted
ahead of this phase's slot, bumping it to Phase 9. Only the filenames had
been renamed; the text inside every file hadn't. Fixed before starting
any real work on this WP, so the directory is internally consistent
(Phase 9 throughout, `WP-09-0X` cross-references) rather than confusingly
mixed — the two intentional historical references to "Phase 8"/
`WP-08-0X` in `README.md`'s own renumbering explanation were left as
literal history, not fixed away.

## Files created

- `.github/ISSUE_TEMPLATE/bug_report.yml` — structured GitHub issue form:
  what happened, expected behavior, reproduction steps, a role dropdown
  (the four real app roles plus "not signed in"), optional evidence.
- `.github/ISSUE_TEMPLATE/support_request.yml` — structured form: what's
  needed, why, an urgency dropdown, an optional deadline field.
- `.github/ISSUE_TEMPLATE/config.yml` — disables blank issues; one
  contact link pointing "I have a question" at `docs/manuals/` instead.
- `docs/support-workflow.md` — how an issue moves from filed to closed: a
  small fixed label set (`bug`/`support`/`needs-triage`/`blocking`, no
  finer severity scale — proportionate to one Division running one meet
  at a time), the triage decision tree (fixable now / not fixable now /
  role-change support request / user error / disguised feature request),
  and an explicit "what this deliberately doesn't do" section (no CI, no
  bot, no SLA, no automated notifications).
- `docs/reports/phase-09/WP-09-01-completion.md` — this file.

## Files modified

- `docs/turnover.md` — the escalation table's "How" column now points at
  the new workflow for the two rows this WP built real templates for
  ("need a role promoted" → Support request template, "a bug in the app
  itself" → Bug report template); the other three rows (app down,
  database problem, feature/scope request) are untouched — genuinely
  different categories (urgent direct-contact, or a business decision)
  that don't fit either template, left as `_[fill in]_` honestly rather
  than force-fit. Added a `docs/support-workflow.md` cross-reference to
  "See also."
- `docs/phases/phase-09-post-deployment-support/{README.md,
  CHECKLIST.md, DESIGN-NOTES.md, WP-09-01-*.md, WP-09-02-*.md,
  WP-09-03-*.md}` — the Phase 8→9 numbering fix described above.

No `app/`, `database/`, `routes/`, `composer.json`/`.lock`, or
`package.json`/`.lock` file was touched — confirmed by `git diff --stat`
against those paths, empty. This WP is documentation and GitHub
configuration only, exactly as scoped.

## Test results

No test changes — none were needed or expected (no code changed). Full
suite reran to confirm the gate is still green: **703/703 passing, 3,716
assertions** — identical to the count before this WP, zero regressions.

## Quality results

- Pint: **PASS**
- PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — 703/703, 3,716 assertions
- ESLint: **PASS** · Prettier (`resources/`, the project's own formatting
  scope — `package.json`'s `format`/`format:check` scripts only target
  that directory, confirmed by reading them rather than assumed):
  **PASS** (two pre-existing, unrelated files —
  `resources/js/pages/registry/school-districts.tsx`/`schools.tsx` —
  remain flagged; `git diff --stat` confirms neither was touched by this
  WP or anything in Phase 8.5, out of scope, not fixed here) · `tsc
  --noEmit`: **PASS**
- `npm run build`: **PASS** (identical output chunk hashes to before —
  confirms zero frontend code changed)
- All three new `.github/ISSUE_TEMPLATE/*.yml` files parsed as valid YAML
  (verified with `js-yaml`, not just eyeballed)

One real, if minor, mid-task correction: an early pass ran Prettier
directly on `docs/turnover.md` and `docs/support-workflow.md`, which
reformatted the *entire* pre-existing `turnover.md` file's markdown
tables/emphasis style (Prettier's Markdown formatter, not just my new
lines) — including converting `*italic*` to `_italic_` throughout and
introducing an awkward line break inside a backtick code span. Caught
this, confirmed `package.json`'s `format`/`format:check` scripts don't
even cover `docs/` (only `resources/`), reverted `turnover.md` to its
committed state, and reapplied only the two intended edits by hand
instead of a whole-file reformat.

## Remaining issues

None blocking. Both new "How" cross-references in `docs/turnover.md`
still leave "Who to contact" as a placeholder — a real name/contact is a
business decision for whoever owns this deployment, not something this
WP can originate (matches this doc's own stated philosophy: "a
placeholder is more honest than a guess"). The escalation table's
remaining `_[fill in]_` "How" cells (app down, database problem,
feature/scope request) are unchanged — genuinely different categories
from what GitHub Issues templates are for.

## Recommended next work package

```text
WP-09-02 — Monitoring & Health-Check Routine
```
