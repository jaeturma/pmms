# PMMS Public Portal Guide

For anyone visiting PMMS **without signing in** — parents, students,
schools, and the general public following a published meet. No account
needed for anything on this page.

Nothing about a meet is visible here until an Administrator or Organizer
**publishes** it — see [`organizer-manual.md`](organizer-manual.md) §14.
An unpublished meet, or one still in Draft, simply doesn't exist as far as
this guide is concerned.

## 1. Home page

`/` lists every published meet — name, school year, dates, venue, and
status — each linking into its own meet page. The five most recent
published announcements (general or meet-specific) also show here.

## 2. A meet's page

`/meets/{meet}` — pick a day (a day-selector defaults to today while the
meet is running, otherwise the first scheduled day) to see that day's
schedule grouped by venue: time, event, and any note. Below the schedule,
a **venue guide** lists each venue's name and address (nothing else —
internal venue notes never appear here).

If any match in this meet currently has a live scoring session running,
a **"Live now"** section lists it with a link straight into its
scoreboard (§4) — this is the only way to find a live score without
already having the direct link.

The meet's own published announcements (if any) also appear here.

## 3. Results and medal tally

- `/meets/{meet}/results` — **official, validated results only**. Each
  event shows rank (ties marked), the athlete's name and school, and
  their mark/time — nothing else about the athlete. "Official as of"
  shows when it was validated, not who validated it. Filter by sport.
- `/meets/{meet}/tally` — the medal standings: municipality/district
  totals first (the official verdict for the meet), each school's own
  medal count below as a reference. Computed the same way, from the same
  data, as the internal tally — it can never disagree with it, and
  updates automatically if a result is later corrected. Filter by sport.

If a result is later corrected (reopened, re-encoded, re-validated), it
simply disappears from both pages until validated again — there's no
stale or half-updated state to see.

## 4. Live scoreboard (provisional — read this)

`/meets/{meet}/matches/{match}/scoreboard` — a read-only view of a
match's live running score, if one is currently active, reached from a
meet page's "Live now" section (§2) or a shared link.

**This is explicitly not an official result.** The page carries a visible
"Live score — provisional, not the official result" badge for exactly
this reason — a live score is an in-progress number an operator is
tracking during play; the real outcome is only ever the validated result
in §3, encoded and checked separately afterward. If there's no live
session for a match, you'll simply see an empty state, not an error.

The score updates automatically roughly every 5 seconds. No account
controls exist on this page at all — nothing here can be edited by
anyone viewing it.

## What's never public

By design, no page on this portal — now or in any future addition — shows
an athlete's birthdate, LRN, grade level, photo, contact details,
guardian information, eligibility documents, protests, incidents, audit
data, user accounts, unvalidated results, or internal venue notes. A
placed athlete's **name, school, and result** is the most PMMS ever shows
publicly about an individual.

## See also

- `docs/public-portal.md` — the technical reference and full privacy
  baseline behind everything above.
- `docs/live-scoring.md` — how the live scoreboard works internally.
- [`viewer-manual.md`](viewer-manual.md) — signing in doesn't add
  anything beyond what's on this page; a Viewer account exists for a
  different reason (internal registries/reports).
