# PMMS Public, Reporting, and Projection Data

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/reporting-search-and-read-model-runtime.md](../01-architecture/reporting-search-and-read-model-runtime.md) · [logical-data-architecture.md](logical-data-architecture.md) · [information-classification-and-privacy.md](information-classification-and-privacy.md)

This document defines the persistence-layer treatment of public projections, cross-context read models, search indexes, and cache boundaries. **No physical table, view, or search index is created here.**

---

## 1. Public Projections

| Projection | Source | Rebuild Trigger |
|---|---|---|
| Public schedule projection | Venue and Schedule (BC-14) | `VenueScheduleChanged` |
| Public result projection | Official Results (BC-16) | `ResultPublished` |
| Medal tally projection | Medal Tally (BC-18) | `MedalTallyRecalculated` (only once certified/published) |
| Public athlete-profile projection | Participant Registry (BC-07), filtered | Registration/publication-approval events |
| Public delegation projection | Delegation Management (BC-06) | Delegation confirmation |
| Public venue projection | Venue and Schedule (BC-14) | Venue readiness/schedule changes |
| Announcement projection | Media and Communications (BC-28) | `AnnouncementPublished` |
| Tournament-progress projection | Tournament Management (BC-12) | `DrawCompleted`, `MatchScheduled` |

### Rules

- **Projections are rebuildable** — every projection can be regenerated from its source data at any time; none holds information that does not also exist, more authoritatively, upstream.
- **Projections carry source version** — a public result projection states exactly which Official Result version it reflects (per [temporal-history-and-versioning-model.md, Section 4](temporal-history-and-versioning-model.md#4-versioning-and-supersession)).
- **Projections carry publication status** — even within BC-29's own storage, a distinction exists between "built and ready" and "actually visible to the public," so a projection can be prepared without prematurely exposing it.
- **Projections carry freshness** — a `projected_at`/`source_updated_at` pair lets any consumer (including the public UI itself) show "last updated" information honestly.
- **Unpublished data must never appear** — the projection-build process reads only from sources already in a Published/Certified state; there is no code path where an in-progress or held record reaches a public projection.
- **Held or superseded results propagate correctly** — when Protest and Appeals places a hold on a result, the projection-rebuild trigger must remove or flag the now-held result from the public projection, not leave a stale "published" version visible.
- **Public projections are privacy-filtered** — per [information-classification-and-privacy.md](information-classification-and-privacy.md), built with only approved fields from the start, never filtered as an afterthought at read time.
- **Public projection failure never corrupts source records** — a failed rebuild leaves the projection stale (with its freshness timestamp reflecting that), never partially written in a way that could expose inconsistent data.
- **Correction propagation is testable** — per [testing-architecture.md](../01-architecture/testing-architecture.md) (Phase 0.4), a specific test scenario: "source result corrected → projection rebuild → public view reflects correction, old value never resurfaces."

## 2. Read Models and Analytics

| Read Model | Source Contexts | Update Frequency | Freshness Expectation | Rebuildable | Sensitivity | Public Eligible | Historical Snapshot | Failure Behavior |
|---|---|---|---|---|---|---|---|---|
| Meet readiness | BC-05, BC-14, BC-21–27 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged, not blocking |
| Registration completion | BC-06, BC-08 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged |
| Eligibility progress | BC-09 | Near-real-time | Minutes | Yes | Restricted (case-level), Internal (aggregate) | No | No | Stale flagged |
| Delegation status | BC-06 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged |
| Accreditation issuance | BC-19 | Near-real-time | Minutes | Yes | Confidential | No | No | Stale flagged |
| Venue schedule board | BC-14 | Near-real-time | Seconds–minutes | Yes | Public (once published) | Yes | No | Fallback to last-known-good |
| Tournament progress | BC-12 | Near-real-time | Seconds–minutes | Yes | Public (published only) | Yes | No | Fallback to last-known-good |
| Live scoring board | BC-15 | Real-time (best-effort) | Seconds | Yes | Internal (provisional, not public per se — see [../01-architecture/realtime-architecture.md, Section 4](../01-architecture/realtime-architecture.md#4-provisional-vs-published-distinction-on-broadcast-channels)) | Provisional-labeled only | No | Falls back to normal query on Reverb failure |
| Certified result board | BC-16 | Near-real-time | Seconds–minutes | Yes | Public (published only) | Yes | Yes (per version) | Fallback to last-published |
| Medal tally | BC-18 | On recalculation | Minutes | Yes | Public (published only) | Yes | Yes (per snapshot) | Fallback to last-published |
| Committee operations | BC-05 and specialized contexts | Periodic | Hours | Yes | Internal | No | No | Stale flagged |
| Medical incident summary | BC-21, de-identified aggregate only | Periodic | Hours | Yes | **Restricted aggregate, never row-level** | No | No | Stale flagged |
| Billeting occupancy | BC-22 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged |
| Meal distribution | BC-23 | Periodic | Hours | Yes | Internal | No | No | Stale flagged |
| Transportation status | BC-24 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged |
| Security incidents | BC-25 | Near-real-time | Minutes | Yes | Restricted | No | No | Stale flagged |
| Finance monitoring | BC-26 | Periodic | Hours | Yes | Restricted | No | No | Stale flagged |
| ICT support | BC-27 | Near-real-time | Minutes | Yes | Internal | No | No | Stale flagged |
| Public feed | BC-16, BC-18, BC-14, BC-28 → BC-29 | Near-real-time | Seconds–minutes | Yes | Public | Yes | Per-source-version | Fallback to last-published |
| Post-event analytics | All contexts, aggregate | Scheduled (post-closure) | N/A (historical) | Yes | Internal/Restricted per underlying source | No (unless specifically approved) | Yes (frozen at closure) | Regenerated on demand |

## 3. Search Indexes

Per [../01-architecture/reporting-search-and-read-model-runtime.md, Section 3](../01-architecture/reporting-search-and-read-model-runtime.md#3-search-architecture) (Phase 0.4), search begins as MySQL-backed (indexed columns, full-text where MySQL's native capability suffices) and only escalates to a dedicated search engine once a specific, measured need is demonstrated. At the persistence layer:

- Search indexes are **derived and rebuildable** — never an independent source of truth.
- Search results respect authorization and classification (a Restricted document's metadata never appears in a search result for an unauthorized searcher) — filtering happens at index-build/query time, consistently with projection privacy-filtering (Section 1).
- No dedicated search infrastructure (Elasticsearch, Meilisearch, etc.) is provisioned in this phase.

## 4. Cache Boundaries (Cross-Reference)

Full cache rules are defined in [../01-architecture/caching-and-session-architecture.md, Section 1](../01-architecture/caching-and-session-architecture.md#1-caching-architecture) (Phase 0.4) — this document's role is to confirm that public projections and read models are themselves natural cache targets (their freshness requirement is "near-real-time," not "instantaneous," per the tables above), while the underlying *authoritative* data they're built from is never itself cached as a substitute for a real query when correctness matters (e.g., checking current entry-lock status before generating a draw always reads the authoritative table, never a cache).

## 5. Open Questions

- Specific freshness targets (seconds vs. minutes) per read model — require pilot-meet operational data, consistent with the general pattern of deferring numeric targets established since [Phase 0.1 success-framework.md](../00-product/success-framework.md#11-baseline-requirements).
- Whether post-event analytics ever exposes anything publicly beyond what's already in the standard public projections — no current requirement identified.

Tracked in [data-open-decisions.md](data-open-decisions.md).
