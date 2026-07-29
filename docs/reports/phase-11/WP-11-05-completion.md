# WP-11-05 — Completion Report

FAQs Page. Status: **done**.

## Repository findings

Confirmed no `Accordion` primitive existed yet
(`resources/js/components/ui/`) and no `@radix-ui/react-accordion` in
`package.json` — needed to add it, per the WP's own explicit allowance
("this project has added shadcn primitives before without counting as
a new dependency"). Confirmed `tw-animate-css` (already a dependency,
already imported in `app.css`) ships the `accordion-down`/`accordion-up`
keyframes shadcn's Accordion component expects — no new CSS needed.
Confirmed the global `prefers-reduced-motion` reset (`@media
(prefers-reduced-motion: reduce) { *, ::before, ::after { ... } }`,
Phase 8.5-02) is a universal selector, so it covers the new accordion
animation automatically, same as every other animated class in this
app.

**Real mishap caught and fixed during this WP**: ran `npx shadcn@latest
add accordion --yes` first, following the WP's suggested "standard
CLI... pattern" literally — it auto-detected `pnpm` (this repo has a
vestigial `pnpm-workspace.yaml` from the original `create-laravel`
scaffold, never actually used since) and, before failing on an
unrelated build-script-approval prompt, had already modified
`package.json` (adding a different package, `radix-ui`, a bundled
meta-package — not `@radix-ui/react-accordion`), appended to
`pnpm-workspace.yaml`, and generated a brand-new `pnpm-lock.yaml` this
repo has never had. Caught it via `git status` before proceeding,
reverted `package.json`/`pnpm-workspace.yaml` with `git checkout --`,
deleted the stray `pnpm-lock.yaml`, and instead ran the project's own
actual package manager directly: `npm install @radix-ui/react-accordion`
— confirmed via `git diff package.json` that this produced exactly the
one expected line, and `resources/js/components/ui/accordion.tsx` was
then hand-written to match this project's existing shadcn-generated
primitives' exact style (double quotes, 2-space indent — confirmed via
`.prettierignore`/`eslint.config`'s `resources/js/components/ui/*`
exclusion that this is deliberate and these files are never
reformatted).

## Implementation

- `package.json`/`package-lock.json` — added `@radix-ui/react-accordion`
  (`^1.2.20`), matching the dozen other individual `@radix-ui/react-*`
  packages already in this project, one per shadcn primitive in use.
- `resources/js/components/ui/accordion.tsx` — new, hand-written to the
  standard shadcn "new-york" style (matches `dialog.tsx`/`collapsible.tsx`'s
  exact conventions): `Accordion`/`AccordionItem`/`AccordionTrigger`/
  `AccordionContent`.
- `app/Http/Controllers/PortalController.php` — new `faqs()` method:
  `Meet::published()->findOrFail()` + `meetSummary()`, nothing else —
  no new query.
- `routes/web.php` — one new additive route, `GET /meets/{meet}/faqs`
  → `PortalController::faqs`, named `public.faqs`, same
  `whereNumber('meet')` constraint as every other public meet route.
- `resources/js/pages/public/faqs.tsx` — new page: 6 FAQ items in an
  `Accordion`. The one meet-dependent answer ("When is this meet
  happening?") interpolates real `meet.*` fields; every other answer
  restates already-documented portal behavior (publication/validation/
  live-provisional rules from `docs/public-portal.md`, the rank-order
  disclaimer already shown verbatim on `tally.tsx`) — no invented
  claim.
- `npm run build` rerun to regenerate Wayfinder's `resources/js/routes/
  public/index.ts` with the new `faqs()` helper (gitignored, generated).

## Tests

New `tests/Feature/PublicFaqsTest.php` (2 tests): guest access +
unpublished-meet 404 with the real meet summary present, and a
`missing()`-style check confirming only the public-safe `meetSummary()`
fields are exposed (no `is_published`/`is_active`/timestamps).

## Quality gate

- Pest: **727/727** passed, 4,056 assertions (+2 tests, +35 assertions
  over WP-11-04's baseline of 725/4,021).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: `faqs.tsx` (this WP's own file) reformatted via a
  **targeted** `prettier --write` on that one path (import-order only);
  `accordion.tsx` is correctly excluded from Prettier/ESLint entirely
  (`.prettierignore`/`eslint.config`'s `resources/js/components/ui/*`
  rule, confirmed before writing it, not assumed). The same 2
  pre-existing, unrelated drifted files from WP-11-02/03/04
  (`registry/school-districts.tsx`, `registry/schools.tsx`) remain
  untouched.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — new FAQs page entry in the Pages section.
- `docs/phases/phase-11-public-portal-completion/CHECKLIST.md` —
  WP-11-05 checked off.

## Remaining issues

None. FAQs is not yet reachable from the header nav/footer or
`PublicBottomNav` — expected, per the phase's own sequencing (WP-11-08
wires all five new pages in together). Flagging for whoever runs
WP-11-06 (Search): confirm no leftover `pnpm-lock.yaml`/modified
`pnpm-workspace.yaml` reappears if any future WP reaches for a shadcn
CLI add again — prefer `npm install <package>` directly plus a
hand-written primitive file, the technique proven safe in this WP.

## Git status

`git diff --stat` against `app`/`routes`/`resources`/`package.json`/
`package-lock.json` shows exactly the expected changes: `PortalController.php`,
`routes/web.php`, the already-modified `tally.tsx` (untouched further
this WP), `package.json`/`package-lock.json` (the one new dependency),
plus new untracked `faqs.tsx`, `accordion.tsx`, and the test file. No
migration touched. `pnpm-lock.yaml`/`pnpm-workspace.yaml` confirmed
clean of the earlier CLI mishap. Not committed, per rule.

Next: **WP-11-06 — Public Portal Search**, awaiting owner instruction.
