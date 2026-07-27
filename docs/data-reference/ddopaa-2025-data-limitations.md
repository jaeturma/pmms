# DdOPAA 2025 Reference Dataset — Data Limitations

The honest version of "what this dataset is not." Read this alongside
`docs/data-reference/ddopaa-2025-reference-data.md` (what's actually in
it) before using any part of this dataset in a demo, screenshot, or
report where someone might mistake it for the real 2025 event record.

## Why the gap exists

The request's named primary source — a Facebook page — was
**inaccessible to every tool available during this initiative**.
`WebFetch` against it returned only the page's title; zero posts, zero
medal tallies, zero results. Facebook blocks non-authenticated,
non-JavaScript access to post content entirely, and nothing in this
environment can log in or render JavaScript to get past that. The
supporting source (a Provincial Government of Davao de Oro article) was
also blocked directly (`HTTP 403 Forbidden`); every fact attributed to
it anywhere in this dataset comes from a search engine's own synthesis
of that page, not from reading the primary text.

Full detail: `docs/data-reference/ddopaa-2025-source-register.md`.

## What this dataset does NOT contain

- **No complete or official DdOPAA 2025 medal tally**, day-by-day or
  final, for any municipality. Nothing indexed anywhere gives one — this
  was searched for directly and confirmed not found (source register row
  7). The medal tally you see in the app for this meet is derived from
  14 seeded results, only 4 of which carry a corroborated winner; the
  standings themselves, and every full ranking, are synthetic.
- **No verified overall champion.** No source names one.
- **No verified competition schedule.** No source gives dates, times, or
  venues per event beyond the single opening date/venue fact. Every
  `EventSchedule` row seeded by this initiative — including all of WP4's
  live-scoring sample matches — is `SYNTHETIC_DEMO`.
- **No verified individual match scores or full results**, beyond the
  handful of team-level event-winner fragments (source register rows
  4–6), which name a winning team, not a score, not a bracket, not
  silver/bronze.
- **No real student-athlete names, anywhere, under any circumstance.**
  One real athlete's name did surface during research (a boxing gold
  medalist, mentioned in a Scribd document preview) and was **deliberately
  never recorded** — not in the source register, not in any seeder, not
  in this document — per the owner's standing instruction that no real
  athlete name is used without explicit authorization, regardless of
  whether it's already public. Every athlete in this dataset has a
  synthetic name drawn from a fixed pool.
- **No real schools.** No source names an actual school for any of the
  11 municipalities. All 180 seeded schools (177 standard tier + 3 demo
  tier) use plausible DepEd-style names, not a verified roster.
- **No real personal data beyond what the schema already collects for
  demonstration purposes.** No birth dates beyond what `Athlete.birthdate`
  legitimately requires for grade-level derivation, no home addresses,
  no guardian information, no personal phone/email beyond
  `@example.test`-style placeholder contacts, no medical information, no
  eligibility documents, no student IDs, no private account details.
- **No `VERIFIED_OFFICIAL` data anywhere.** Every single record in this
  dataset is `PARTIALLY_VERIFIED` at best (a short, explicit list — see
  the reference-data doc) or `SYNTHETIC_DERIVED`/`SYNTHETIC_DEMO`.

## A note on the one adjacent, easily-confused real number

A Sunstar Davao article reports Davao de Oro's *provincial team* placing
4th at **DAVRAA 2025** (30 gold / 56 silver / 71 bronze) — this is the
**regional** meet, where provinces compete against each other, held
*after* each province's own internal meet. It is a different competition
from DdOPAA and describes the whole province's combined result, not an
internal municipality-vs-municipality tally. It is recorded in the
source register only to flag the distinction; it is not used anywhere in
this dataset and should never be conflated with DdOPAA 2025 standings.

## Why this matters for anyone using this data

Every seeder, wherever it produces a fact from the short
`PARTIALLY_VERIFIED` list, documents exactly which register row backs
it, right next to the code that creates it — so the classification is
never more than one file away from the data it describes. If you are
using a screenshot or export from this dataset in any context where
someone might read it as the real 2025 DdOPAA record, say plainly that
it is demonstration data, not the historical record. If real Facebook
post content becomes available later (the project owner supplies it
directly), that is a future, separately-scoped addition — it does not
get silently retrofitted into this dataset's existing synthetic records.
