# WP-08-06 — Athlete Eligibility Checker UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-07 has
not been started.

## Scoping decision (owner-directed)

This WP's reference (`athlete-eligibility-checker.png` — again the wrong
image list in the WP doc itself, same recurring issue as every prior
WP) shows a fully automated eligibility-rule engine: search an athlete,
pick sport/event/category, click "Check Eligibility," and the system
auto-evaluates 7 rules (age, grade level, residency, school enrollment,
document requirements, event entry limit, duplicate entry) into an
automatic PASS/FAIL per rule and an ELIGIBLE/INELIGIBLE verdict.

That directly conflicts with the real, documented, deliberate product
decision in `docs/eligibility.md`: *"PMMS records documents and human
decisions — it never adjudicates eligibility (automated rules are
policy-dependent and deferred)"* — with "Automated rules, age
adjudication... duplicate entry" explicitly listed as **out of scope**.
WP-08-01's gap assessment flagged this exact conflict as needing an
owner decision before WP-08-06, not a silent restyle.

Presented the owner three options before writing any code: (1) restyle
the real manual-review queue only, no automated rules invented; (2)
build a new single-athlete lookup page (still no invented rules, but a
dedicated search-by-athlete view); (3) actually build the automated
rule engine, reversing the documented "deferred" decision. **The owner
chose option 1** — restyle only. Everything below reflects that.

## What was found and built

Read `EligibilityController`, `EligibilityReview`/`EligibilityDocument`
models, `EligibilityReviewPolicy`, `eligibility/index.tsx`,
`docs/eligibility.md`, and `EligibilityTest.php` before changing
anything. All additions are real, computed from existing data — no
per-rule PASS/FAIL, no automated verdict, no invented "required
documents" checklist (the app has no required-vs-optional document-type
concept to check against).

**Backend** (`EligibilityController`):

- Added a name search — the controller now uses the shared
  `SearchesAndPaginates` trait (`applySearch($query, $search,
  ['athlete.first_name', 'athlete.last_name'])`), the identical pattern
  `EntryController` already uses for the same relation. This is what the
  reference's "Search Athlete" input actually maps to in real,
  buildable scope (its "Scan QR Code" sub-option was not built — no QR
  scanning infrastructure exists anywhere in this app).
- Added `counts` (pending/approved/returned) computed once from a
  cloned base query *before* the status/search filters narrow it, so
  the summary cards reflect the officer's whole scoped queue regardless
  of what's currently filtered — matches the reference's "totals don't
  move when you filter" behavior.
- Added `uploaded_at` (the document's real `created_at`, formatted) to
  each document row.
- Extracted `reviewRow()`/`documentRow()` private methods (with
  explicit array-shape docblocks) out of the inline `->through()`
  closure — needed to resolve a real PHPStan/Larastan "Collection
  template is not covariant" false positive triggered by the nested
  `map()` inside `through()`; also leaves `index()` easier to read.

**Frontend** (`eligibility/index.tsx`):

- Retitled "Eligibility" → "Eligibility Review" (kept honest — not
  "Checker," since no automated checking was built).
- Reused the existing shared `SearchBar` component (already used by
  `athletes/index.tsx` etc.) for the new search input, combined with
  the existing status `Select` — each preserves the other's current
  value when changed.
- Three summary `StatCard`s (Pending review/Approved/Returned) using
  WP-08-02's semantic tone tokens (warning/success, Returned left
  untoned — it's a normal workflow state needing correction, not
  strictly negative).
- Status `Badge`s recolored: pending=warning, approved=success,
  returned=destructive (was default/secondary/outline).
- Each document link now shows its real upload date next to it, with a
  `FileText` icon — reads closer to the reference's document-list
  visual without implying per-document verification (no checkmark
  icon was added, since a checkmark would imply an automated
  "verified" signal this app doesn't compute — only the review-level
  human decision means anything here).

## What was deliberately NOT done

- **No automated PASS/FAIL rule engine** — the owner's explicit choice;
  see the scoping decision above.
- **No "Check Eligibility" button, no per-rule checklist, no
  ELIGIBLE/INELIGIBLE verdict** — none of this exists in the app's data
  model and none was invented.
- **No QR code scanning** — no such infrastructure exists; the search
  input is a plain name search.
- **No sport/event/category filters** — those only make sense for the
  reference's per-event automated check, which wasn't built. The
  existing status filter (and new search) are what's real here.
- **No "Print Result"/"Export PDF"** — there is no automated result to
  export; this queue already has no printable-report equivalent and
  none was added.
- **No document-level checkmark/"verified" indicator** — see above.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache-vhost-routing issue noted in WP-08-05's report;
  status unchanged since then, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **678/678 passing**, 3,444 assertions (2 new tests
in `EligibilityTest`: the queue can be searched by athlete name and
excludes non-matches; summary counts reflect the whole scoped queue
even when the status filter narrows the visible list to one row).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 678/678, 3,444 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files modified

- `app/Http/Controllers/EligibilityController.php` — search, `counts`,
  `uploaded_at`, extracted `reviewRow()`/`documentRow()`
- `resources/js/pages/eligibility/index.tsx` — search bar, summary
  cards, recolored status badges, document upload dates
- `tests/Feature/EligibilityTest.php` — 2 new tests
- `docs/eligibility.md` — scoping decision + WP-08-06 section
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-06
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-07.
- The pmms.app Apache vhost routing issue (noted in WP-08-05) is still
  unresolved.

## Next

WP-08-07 — Public Portal Shell and Branding, on owner instruction (per
this WP's own rule: do not begin the next work package).
