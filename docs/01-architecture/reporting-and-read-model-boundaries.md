# PMMS Reporting and Read-Model Boundaries

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [data-ownership-map.md](data-ownership-map.md) · [high-integrity-domain-rules.md](high-integrity-domain-rules.md)

## Principles

1. **Transactional contexts remain authoritative.** No report, dashboard, or read model is ever the source of truth for a business decision — it is a *view* of decisions made elsewhere.
2. **Reports consume approved data.** A report reads from a context's published projection or event stream, never by reaching directly into another context's authoritative write-store.
3. **Committee reports may use context-owned operational projections** — e.g., a Medical Operations incident summary is a projection owned and controlled by BC-21 itself, not assembled ad hoc by Reporting.
4. **Cross-domain executive reports use read models**, not live joins across multiple contexts' authoritative data.
5. **Historical snapshots may be required** for domains where "what did we know/decide at time T" matters (results, medal tally, eligibility decisions) — see [high-integrity-domain-rules.md](high-integrity-domain-rules.md) for versioning requirements.
6. **Generated documents include source and version references** — a printed result sheet or certificate states which Official Result version it reflects.
7. **Report corrections trace back to the source context.** A report is never "corrected" independently of its source; the source context corrects its record, and the report re-derives.
8. **Analytics must not silently rewrite source records.** BC-33 Reporting and Analytics has read-only access to every other context, structurally.

## Reporting Boundary by Context Type

| Context Type | Reporting Relationship |
|---|---|
| Core high-integrity contexts (BC-09, BC-15, BC-16, BC-17, BC-18, BC-19) | Publish read-only, versioned projections; raw/working data never directly queried by Reporting |
| Supporting operational contexts (BC-05, BC-21–BC-27) | Publish operational dashboards/projections owned by the context itself, consumed by Reporting for cross-committee views |
| BC-29 Public Information | Itself a specialized, public-facing read model — not a further reporting source for internal use |
| BC-33 Reporting and Analytics | Pure consumer across all contexts; owns no authoritative business data |

## Candidate Read Models

Each candidate below is described at a conceptual level — **no database views, schemas, or query designs are specified** (per working rule 5–8 and documentation quality requirements).

| Read Model | Purpose | Primary Source Context(s) | Consumers | Freshness Expectation | Privacy Filtering Needed |
|---|---|---|---|---|---|
| Meet readiness dashboard | Aggregate readiness signals across all committees ahead of activation/competition | BC-05, BC-14, BC-21–BC-27 | Meet Director, Organizing Committee | Near-real-time | Low |
| Delegation registration status | Track registration/eligibility completion per delegation | BC-06, BC-08, BC-09 | Secretariat, Delegation heads | Near-real-time | Medium (no cross-delegation PII leakage) |
| Eligibility progress | Track case status across all pending eligibility cases | BC-09 | Secretariat, Schools Division Office | Near-real-time | High (case detail restricted) |
| Accreditation issuance status | Track credential issuance progress by category | BC-19 | Accreditation Officers | Near-real-time | Medium |
| Venue schedule board | Present the current schedule for a venue | BC-14 | Organizing Committee, public (via BC-29) | Near-real-time | Low |
| Tournament progress | Track draw/bracket progression per event | BC-12 | Tournament Managers, public (via BC-29) | Near-real-time | Low |
| Live result board | Present certified/published results as they occur | BC-16 | Delegations, public (via BC-29) | Near-real-time (target) | Low (published data only) |
| Medal tally board | Present current published medal tally | BC-18 | Public (via BC-29), Organizing Committee | Near-real-time (on recalculation) | Low |
| Committee operations dashboard | Cross-committee task/readiness view | BC-05 and each specialized committee context | Organizing Committee | Periodic (not necessarily real-time) | Low |
| Medical incident summary | Aggregate, de-identified incident volume/severity trends | BC-21 | Medical Team lead, Organizing Committee (aggregate only) | Periodic | **Critical** — must be de-identified for any non-Medical consumer |
| Billeting occupancy | Current occupancy vs. capacity | BC-22 | Billeting Committee | Near-real-time | Low |
| Transport status | Current trip/dispatch status | BC-24 | Transportation Committee | Near-real-time | Low |
| Food distribution summary | Meal counts distributed vs. entitled | BC-23 | Food Committee | Periodic | Low |
| Finance monitoring summary | Budget vs. expense tracking per committee | BC-26 | Finance Committee, Organizing Committee | Periodic | Medium |
| ICT support dashboard | Open tickets, device/readiness status | BC-27 | ICT Committee | Near-real-time | Low |
| Public portal feed | The public-facing aggregation of schedules/results/tally/advisories | BC-14, BC-16, BC-18, BC-28 (via BC-29) | Public | Near-real-time (target), explicitly eventual | N/A (already filtered before reaching BC-29) |
| Executive post-event summary | Cross-domain summary for DepEd Leadership after meet closure | Nearly all contexts | DepEd Leadership | Post-event (one-time/periodic) | Medium — aggregate only, no individual PII |

## Data Freshness

- **Near-real-time** targets apply to operationally time-sensitive read models (results, schedules, medal tally) but are always explicitly **eventual consistency**, never a guarantee of instantaneous reflection — see [phase-0.2-domain-architecture.md, Section 9](phase-0.2-domain-architecture.md#9-transaction-and-consistency-boundaries).
- **Periodic** read models (finance, committee dashboards) may refresh on a schedule (e.g., every few minutes or on-demand) without integrity impact.
- Every read model should expose its own last-refreshed timestamp to consumers, so staleness is visible rather than silently assumed away.

## Corrections and Traceability

When an authoritative source corrects a record (e.g., a result is corrected following a protest), the correction:

1. Occurs first and only in the authoritative context (BC-16 in this example).
2. Propagates to dependent read models (BC-18 Medal Tally, BC-29 Public Information, BC-33 Reporting) through the normal event/projection pipeline — never through a direct patch to the read model itself.
3. Is visible in the read model as a correction (e.g., "Result updated — see history"), not a silent value change, consistent with [high-integrity-domain-rules.md](high-integrity-domain-rules.md).

## Privacy Filtering

Read models that aggregate data originating in restricted contexts (Medical Operations, Eligibility, Participant Registry) must apply privacy filtering **at the point the projection is built**, not rely on the consuming report to filter afterward. This mirrors the Anti-Corruption Layer principle from [context-map.md](context-map.md): a read model consuming from BC-21 Medical Operations, for instance, should only ever receive already-de-identified aggregate figures, never row-level medical detail, regardless of who requests the report.

## High-Volume Public Delivery and Analytics Isolation

- BC-29 Public Information and BC-33 Reporting and Analytics are architecturally isolated from the write path of every transactional context: a spike in public traffic (e.g., during a medal tally announcement) or a heavy analytics query must not degrade the ability of BC-15 Scoring or BC-20 Access Validation to continue capturing live operational data.
- This isolation is a **principle** for Phase 0.2; the specific technical mechanism (caching layer, read replica, queued projection rebuild, etc.) is deferred to the architecture phase that follows role/permission design (Phase 0.3) and is explicitly out of scope here.
