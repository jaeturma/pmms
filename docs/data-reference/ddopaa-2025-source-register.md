# DdOPAA 2025 Source Register

Source inventory for the proposed Davao de Oro Provincial Athletic
Association (DdOPAA) 2025 reference dataset. Compiled via `WebSearch`/
`WebFetch` — no browser/login-based access was available, which turned
out to be the decisive constraint (see "Critical finding" below). Every
row is classified per the requested scheme:
`VERIFIED_OFFICIAL` / `PARTIALLY_VERIFIED` / `SYNTHETIC_DERIVED` /
`SYNTHETIC_DEMO`.

## Critical finding: the primary source is inaccessible

**The Facebook page — the requested primary reference source — could not
be read at all.** `WebFetch` against
`https://www.facebook.com/p/Davao-de-Oro-Provincial-Athletic-Association-2025-61571742241678/`
returned only the page's title; zero posts, zero medal tallies, zero
results. This isn't a partial limitation — Facebook blocks non-
authenticated, non-JavaScript access to post content entirely, and no
tool available in this session can log in or render JS. **Nothing in
this register is sourced from that page**, despite it being requested as
the primary source. This materially changes what's actually achievable
from Parts 2–3, 8, and 9 of the request — see the implementation plan in
my reply for how I'm proposing to handle that gap.

The Provincial Government article (the supporting source) was also
blocked directly (`HTTP 403 Forbidden` from `davaodeoro.gov.ph`) — every
fact attributed to it below comes from `WebSearch`'s own synthesis of
that page (and others), not from reading the primary text myself. That's
why even the "basic facts" below are `PARTIALLY_VERIFIED`, not
`VERIFIED_OFFICIAL` — I have not personally read a primary source
document for any of this, only search-engine summaries and Scribd
document previews.

## Register

| # | Source title | Organization | Pub. date | URL | Category | Sport | Delegation | Complete? | Readable? | Verified? | Classification | Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | "Davao de Oro Kicks Off Provincial Athletic Meet 2025 with Spirit of Unity and Sportsmanship" | Provincial Government of Davao de Oro (official website) | 2025-01 (exact date not confirmed) | https://davaodeoro.gov.ph/davao-de-oro-kicks-off-provincial-athletic-meet-2025-with-spirit-of-unity-and-sportsmanship/ | Event announcement | General | All 11 | No — direct fetch blocked (403); only have `WebSearch`'s synthesis | Partially — snippet only, not primary text | No — not read directly | `PARTIALLY_VERIFIED` | Snippet reports: opened Jan 17, 2025; Maragusan; 11 municipalities; ~5,416 athletes; sports = athletics, basketball, volleyball, swimming; named officials (Gov. Dorothy Montejo-Gonzaga, Regional Sports Director Alim Maguindanao, Cong. Ruwel Peter Gonzaga, OIC SDS Phoebe Gay Refamonte, Mayor Angelito Cabalquinto). Matches the request's own "supporting source" facts closely. |
| 2 | Davao de Oro Provincial Athletic Association 2025 (Facebook page) | Claimed official DdOPAA page | — | https://www.facebook.com/p/Davao-de-Oro-Provincial-Athletic-Association-2025-61571742241678/ | — | — | — | No — page title only | No | No — could not authenticate the page is genuinely official, could not read any post | **INACCESSIBLE** | The requested primary source. Zero content retrieved. Not usable for any of Parts 2, 3, 8, 9's data as specified. |
| 3 | "2025 Davao de Oro Athletic Meet Highlights" | Uploaded to Scribd (uploader/authorship not independently confirmed) | Undated | https://www.scribd.com/document/818715902/News-1 | Event summary | General | Maragusan (host) | No — preview text only ("over 500 student athletes...") | Partially | No | `PARTIALLY_VERIFIED` | Conflicts with source #1's "~5,416 athletes" figure — likely a different count basis (e.g. one day's attendance vs. total registered) or a different, less reliable source. Flagged, not reconciled. |
| 4 | "DDOPAA NEwsletter Final 1 Not" | Uploaded to Scribd (uploader/authorship not independently confirmed) | Undated | https://www.scribd.com/document/834888925/DDOPAA-NEwsletter-Final-1-Not | Results/highlights | 3x3 Basketball (Boys), Volleyball (Women's), Artistic Gymnastics (Men's) | Montevista, Nabunturan, New Bataan | No — 4-page document, only page-1-level preview text surfaced | Partially | No | `PARTIALLY_VERIFIED` | Team nicknames: "Montevista Blazing Fighters" (won 3x3 Basketball Boys), "Nabunturan Black Mamba" (won Women's Volleyball), "New Bataan Rock Wreckers" (Men's Artistic Gymnastics). Repeats source #1's opening facts. |
| 5 | "Ddopaa Final Final" | Uploaded to Scribd (uploader/authorship not independently confirmed) | Undated | https://www.scribd.com/document/834888932/Ddopaa-Final-Final | Results/highlights | 3x3 Basketball, Volleyball, Artistic Gymnastics | Montevista, Nabunturan | No — preview text only | Partially | No | `PARTIALLY_VERIFIED` | Corroborates source #4's team names/wins. No medal counts, no officials named in the visible excerpt. |
| 6 | (Search snippet only — exact document not individually fetched) | Uploaded to Scribd / search synthesis | Undated | via WebSearch, no single URL confirmed | Results | Boxing, Volleyball | Nabunturan, Mawab, Maragusan | No | No — search-engine synthesis only | No | `PARTIALLY_VERIFIED` | "Nabunturan Black Mamba won a total of 4 golds in the Boxing Championship"; "Mawab Pick Hammer defeated defending champion Maragusan Maroon Knights in the [volleyball] semifinals." Also surfaced one real student-athlete's full name as a boxing gold medalist — **deliberately not recorded in this register or anywhere else in this dataset**, per the request's own instruction to never use real athlete names without explicit owner authorization, regardless of whether the name already appears in a public post. |
| 7 | (No source found) | — | — | — | **Complete/official medal tally (day-by-day or final, any municipality)** | All | All 11 | — | — | — | **NOT FOUND** | Searched directly for this; nothing indexed gives a full per-municipality medal count or names an overall DdOPAA 2025 champion. Only fragments (individual event winners, team nicknames) were found — see rows 4–6. |
| 8 | "Davao de Oro climbs to 3rd..." / DAVRAA 2025 medal tally sources | Sunstar Davao / DepEd Division of Davao Oriental (Facebook) | 2025 | https://www.sunstar.com.ph/davao/sports/davao-de-oro-climbs-to-3rd-as-thunderbolts-build-on-2025-gains | Medal tally | All | Davao de Oro (as a whole province) | Partial | Yes (article) | Not read directly | `PARTIALLY_VERIFIED` | **Different competition, do not conflate**: DAVRAA is the *regional* meet (provinces compete against each other, held after each province's own internal meet); "30 gold / 56 silver / 71 bronze, 4th place" describes Davao de Oro's *provincial team's* performance *at DAVRAA*, not an internal DdOPAA municipality-vs-municipality tally. Recorded here only to explicitly flag the distinction, not as usable DdOPAA data. |

## What this register does NOT support

Per Part 3's rule ("never invent historical data and label it as
official" / "do not fill missing historical values with invented
values"), the fragments above are **not sufficient** to construct, and
this dataset will **not** claim to contain:

- A complete or official DdOPAA 2025 medal tally, day-by-day or final,
  for any municipality.
- An official overall champion.
- A verified competition schedule (dates/times/venues per event).
- Verified individual match scores, beyond the handful of event-winner
  fragments in rows 4–6 above (which name a winning team/nickname, not a
  score).
- Any real student-athlete's name, regardless of whether it already
  appears in a public post (row 6's note).

## What it does support (all `PARTIALLY_VERIFIED`, not `VERIFIED_OFFICIAL`)

- The meet was held starting January 17, 2025, hosted by Maragusan
  (Maragusan Grandstand Arena), with all 11 Davao de Oro municipalities
  participating.
- Sports confirmed across sources: Athletics, Basketball (incl. a 3x3
  format), Volleyball, Swimming, Artistic Gymnastics, Boxing.
- Five real delegation/team nicknames: Montevista Blazing Fighters,
  Nabunturan Black Mamba, New Bataan Rock Wreckers, Mawab Pick Hammer,
  Maragusan Maroon Knights.
- A handful of individual event outcomes (team-level, not athlete-level
  beyond the one name deliberately excluded): Montevista won 3x3
  Basketball (Boys); Nabunturan won Women's Volleyball and 4 Boxing
  golds; New Bataan won Men's Artistic Gymnastics; Mawab beat Maragusan
  in a Volleyball semifinal.

See my reply for the implementation plan (Part 15) built around this —
in particular, how I'm proposing to handle the gap between what was
requested (a `VERIFIED_OFFICIAL` dataset primarily sourced from
Facebook) and what's actually achievable (a small set of
`PARTIALLY_VERIFIED` facts, with everything else honestly `SYNTHETIC_*`).
