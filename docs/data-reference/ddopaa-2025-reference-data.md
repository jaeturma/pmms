# DdOPAA 2025 Reference Dataset — What's Actually in It

This is the reference document for the DdOPAA 2025 seed data, organized
by classification. It exists so anyone looking at the dev/demo
environment — or at a screenshot from it — can tell at a glance which
parts resemble the real 2025 Davao de Oro Provincial Athletic
Association meet and which parts are invented for demonstration
purposes. See `docs/data-reference/ddopaa-2025-source-register.md` for
the full source inventory this classification is built from, and
`docs/data-reference/ddopaa-2025-data-limitations.md` for what this
dataset explicitly does **not** claim.

Nothing here reaches `VERIFIED_OFFICIAL` — the request's named primary
source (a Facebook page) was inaccessible to every tool available during
this initiative, and the supporting government-site article could only
be read via search-engine synthesis, not a primary text read. Every fact
below is `PARTIALLY_VERIFIED` at best.

## Classification key

| Label | Meaning |
|---|---|
| `PARTIALLY_VERIFIED` | Corroborated across independent search/document-preview sources, but never read from a primary text — the highest confidence anything in this dataset reaches. |
| `SYNTHETIC_DERIVED` | Realistic data generated to complete a structure a verified fact only partially describes (e.g. a full school roster when only "11 municipalities participated" is verified). |
| `SYNTHETIC_DEMO` | Pure demonstration data with no reference basis at all — most individual athletes, schools, matches, and scores. |

## `PARTIALLY_VERIFIED` facts (the complete list — nothing else in this dataset claims this status)

- The meet was held starting **January 17, 2025**, hosted by
  **Maragusan** (Maragusan Grandstand Arena), with all **11 Davao de Oro
  municipalities** participating.
- Sports confirmed across sources: **Athletics, Basketball (including a
  3x3 format), Volleyball, Swimming, Artistic Gymnastics, Boxing**.
- Five real delegation/team nicknames: **Nabunturan** "Black Mamba,"
  **Montevista** "Blazing Fighters," **New Bataan** "Rock Wreckers,"
  **Mawab** "Pick Hammer," **Maragusan** "Maroon Knights."
- A handful of team-level (never individual-athlete-level) event
  outcomes: **Montevista** won 3x3 Basketball (Boys); **Nabunturan** won
  Women's Volleyball and is credited with "4 golds" in the Boxing
  Championship; **New Bataan** won Men's Artistic Gymnastics; **Mawab**
  beat **Maragusan** in a Volleyball semifinal.

These are woven into the seeded data everywhere they fit (the meet
record, five district nicknames, the corresponding event winners in the
medal tally) and nowhere else — the other six municipalities keep
whatever nickname they already had (none), and every other event's
outcome is synthetic.

## What's seeded, by source (WP1–WP5)

### Meet, venues, sports catalog (`DdopaaReferenceSeeder`, WP1)

- **Meet** "DdOPAA Meet 2025," school year 2024–2025, `starts_at`
  2025-01-17 (`PARTIALLY_VERIFIED`), `ends_at` 2025-01-24
  (`SYNTHETIC_DERIVED` — no verified closing date exists; a plausible
  one-week span). Active and published.
- **Venues**: "Maragusan Grandstand Arena" (`PARTIALLY_VERIFIED` name),
  plus "Maragusan Sports Complex Gymnasium," "Maragusan Municipal Pool,"
  and "Maragusan Sports Complex Diamond" (all `SYNTHETIC_DEMO` — no
  source names a specific gym, pool, or field).
- **Boxing** added to the sports catalog (`SYNTHETIC_DERIVED` — a
  supported live-scoring board type since Phase 7, but never previously
  in the seeded catalog).
- 12 events across Basketball, 3x3 Basketball, Volleyball, Artistic
  Gymnastics, Swimming, and Boxing, each individually annotated in the
  seeder as `PARTIALLY_VERIFIED` (directly matches a corroborated
  outcome) or `SYNTHETIC_DERIVED` (a realistic gender-paired counterpart
  or baseline event). A "Softball" event was added later by WP4
  (below), entirely `SYNTHETIC_DEMO`.

### Delegations, schools, athletes, personnel (`DdopaaStandardSeeder`, WP2; `DdopaaDemoSeeder`, WP5)

- All **11 real municipalities** registered as approved delegations
  (`PARTIALLY_VERIFIED` — their participation is corroborated; which
  specific person coordinates each delegation is `SYNTHETIC_DEMO`).
- **177 schools** (standard tier) + **3 schools** (demo tier), 10–25 per
  municipality, `SYNTHETIC_DERIVED` DepEd-style names ("{Municipality}
  National High School," "{Municipality} Central Elementary School,"
  etc.) — **not** an actual verified roster for any municipality; no
  source names a single real school.
- **531 athletes** (standard tier) + **18 athletes** (demo tier),
  synthetic Filipino names from a fixed name pool, correctly distributed
  by their own school's grade level (Elementary/Secondary/Integrated).
  100% `SYNTHETIC_DEMO` — no real student-athlete name appears anywhere
  in this dataset (see the limitations doc for why).
- **332 confirmed entries** total distributed across the catalog, with
  six delegation/event pairings specifically guaranteed an entry so the
  `PARTIALLY_VERIFIED` outcomes above have a real athlete to place as
  the winner.

### Results and medal tally (`DdopaaResultsSeeder`, WP3)

- **14 validated results, 69 placements.** Four of them carry a
  `PARTIALLY_VERIFIED` winner (3x3 Basketball Boys → Montevista,
  Volleyball Girls → a Nabunturan/Mawab/Maragusan bracket, Artistic
  Gymnastics Boys → New Bataan, Boxing Boys → Nabunturan as a rank-1
  sweep approximating the "4 golds" note); every other placement in
  those results, and every placement in the remaining ten results, is
  `SYNTHETIC_DERIVED`/`SYNTHETIC_DEMO`.
- The medal tally itself is never stored — it's derived at read time by
  the existing `MedalTallyService::standings()`, exactly as it is for
  every other meet in this application. There is no separate "medal
  award" record.

### Live scoring samples (`DdopaaLiveScoringSeeder`, WP4)

- **9 matches** across Basketball, Boxing, and Softball (one new,
  entirely `SYNTHETIC_DEMO` event — no source has any DdOPAA 2025
  softball data), one each in `Scheduled`, in-progress, and completed
  states. All scores, rounds, and innings are `SYNTHETIC_DEMO` — no
  source has real score/round/inning data for any DdOPAA 2025 match.
  Every session stays provisional; none of it ever touches
  `EventResult`/`ResultPlacement`.

### Demo and load-test tiers (WP5)

- **Demo tier** (`DdopaaDemoSeeder`): 3 municipalities × 6 athletes = 18
  athletes, a quick-to-eyeball fraction of the standard tier.
- **Load-test tier** (`PerformanceBenchmarkSeeder`, reused unchanged
  from WP-06-04): 11 real municipalities, 88 schools, 1,320 athletes —
  entirely `SYNTHETIC_DEMO`, sized for query/page-performance profiling,
  not for resembling the real meet.

See `docs/testing/ddopaa-2025-demo-data-guide.md` for exactly how to
seed each tier and what counts to expect.
