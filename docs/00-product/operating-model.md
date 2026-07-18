# PMMS Operating Model

**Status:** Draft for Architecture and Stakeholder Validation
**Related:** [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#14-operating-model) · [product-scope.md](product-scope.md) · [open-decisions.md](open-decisions.md)

This document expands on the operating model summarized in the Phase 0.1 product foundation document. It describes how PMMS is expected to operate structurally and procedurally — not how its database or APIs are designed.

---

## 1. Multi-Meet Concept

PMMS is expected to support more than one meet over time, and potentially more than one meet concurrently. Each meet is a bounded operational unit with its own:

- Dates and schedule
- Venues
- Committees
- Delegations
- Sports and events
- Officials assignments
- Entries, results, and standings

**Requires confirmation:** whether the initial release must support concurrent meets, or whether sequential (one active meet at a time) is acceptable for launch. See [open-decisions.md](open-decisions.md).

## 2. Meet Ownership

Each meet has an accountable owning role (conceptually, a Meet Director or Meet Administrator) responsible for its configuration and overall execution. Meet ownership is distinct from platform ownership (see Section 3).

**Requires confirmation:** the formal title and authority of this role within DepEd's existing governance structure.

## 3. Organization Ownership

The platform itself (spanning all meets) is owned at an organizational level — initially DepEd. Organization-level ownership governs:

- Master data shared across meets (e.g., school records, sport definitions)
- Platform-wide policy (e.g., data retention, AI usage boundaries)
- Cross-meet reporting and historical records

## 4. Committee Structure

Committees operate within a meet and are responsible for a specific operational domain (e.g., Secretariat, Medical, Transportation, Finance, ICT, Security, Media, Accreditation). Committees:

- Are formed per meet, though membership patterns may repeat across meets.
- Operate with a degree of independence in their own workflows.
- Share data with the central platform rather than maintaining separate records.

**Requires confirmation:** the canonical list of committees for a typical provincial meet, and whether this list is standardized or varies by meet.

## 5. Delegation Structure

A delegation represents a participating group (typically a school or a grouping of schools) entering athletes and coaches into a meet. Delegation structure questions — such as whether schools can belong to multiple delegations, and what grouping unit (municipality, district, cluster) is used — are recorded as open organizational questions in [phase-0.1-product-foundation.md, Section 16](phase-0.1-product-foundation.md#16-organizational-model) and [open-decisions.md](open-decisions.md).

## 6. Sport and Event Structure

Sports and events are configurable rather than hard-coded, reflecting the Product Principle of "configurability over hard-coding." A sport may have multiple events, each with its own format (elimination, round-robin, time-based, judged, etc.). **The specific formats and rules per sport must come from authoritative sports sources, not be invented by the platform or development team** (Product Principle: "rules must be source-backed").

## 7. Venue Operations

A meet may span multiple venues operating concurrently. Each venue:

- Has its own schedule of events.
- May operate with intermittent connectivity (see Section 12).
- Requires local accreditation/access-control validation.
- May have its own public display/scoreboard surface.

## 8. Field Operations

Field operations refer to on-the-ground activity during the competition: score entry, accreditation scanning, medical incident logging, and similar time-sensitive tasks performed primarily via mobile devices. Field operations are prioritized for usability and offline resilience over administrative completeness (see Scope Decision Rules in [product-scope.md](product-scope.md#1-scope-decision-rules)).

## 9. Result Validation Chain

Official results follow a controlled validation chain rather than becoming official the moment they are entered:

1. **Entry** — a score or outcome is entered (e.g., by a scorer or technical official).
2. **Review** — the entry is reviewed by an authorized validating role.
3. **Validation** — the result is confirmed as official by that role.
4. **Publication** — the validated result is released to delegations and/or the public per the publication workflow (Section 10).

**Requires confirmation:** the specific roles authorized to enter versus validate results, and whether this varies by sport. This chain must never allow a single unchecked actor to both enter and finally validate a result for the same event, consistent with the Separation of Duties principle.

## 10. Publication Workflow

Validated results do not automatically become public. Publication is a distinct, controlled step that:

- Occurs only after validation (Section 9).
- Is performed or authorized by a role with publication authority.
- Is logged (who published, what, and when).
- May include a correction process if a published result must later be amended — corrections are visible and attributed, never silent (Product Principle: "no silent changes to official results").

## 11. Escalation Model

Operational issues (disputes, incidents, technical failures) require an escalation path. At a conceptual level:

- Field-level issues escalate to the relevant committee head.
- Committee-level issues escalate to the Meet Organizing Committee / Meet Director.
- Protests and appeals follow a distinct chain involving technical officials and technical delegates (see Section 9 and the meet lifecycle's "Protest or Appeal Handling" stage).

**Requires confirmation:** the authoritative escalation and appeals chain per DepEd/sport governance — this must be sourced from actual DepEd/sport governance policy, not invented.

## 12. Incident Management

Incidents (medical, security, operational) are logged with:

- Type/category
- Time and location
- Reporting role
- Response taken
- Resolution status

Incident data supports both real-time response coordination and post-event reporting (see meet lifecycle stage "Incident and Medical Management").

## 13. Data Stewardship

Every category of data is expected to have an identifiable owning role or committee (Product Principle: "clear ownership of data"). Examples (indicative, not exhaustive):

| Data category | Likely steward |
|---|---|
| Athlete eligibility records | Secretariat / Eligibility validation role |
| Official results | Technical officials / Tournament managers |
| Medical records | Medical committee |
| Accreditation credentials | Accreditation officers |
| Financial records | Finance committee |

**Requires confirmation** as part of Phase 0.2 domain discovery.

## 14. Support Responsibilities

ICT support responsibilities span:

- Pre-meet: device provisioning, network setup, user account setup.
- During meet: on-site technical support, incident response for system issues.
- Post-meet: data backup verification, system wind-down.

## 15. Post-Event Closure

Post-event closure includes financial and operational reconciliation, final reporting, and formal sign-off that the meet record is complete and ready for archiving (see meet lifecycle stages 26–28).

## 16. Records Retention Concept

Meet records are expected to be preserved as institutional knowledge (Product Mission, Section 8 in the foundation document) rather than discarded after each meet. **Retention duration, format, and legal basis require confirmation** from DepEd records-management and data-privacy stakeholders — this is not decided in Phase 0.1. See [open-decisions.md](open-decisions.md).

## 17. Offline and Synchronization Principles

- Field-facing workflows should degrade gracefully under intermittent connectivity rather than fail outright.
- Data captured offline should synchronize when connectivity resumes, with conflicts surfaced rather than silently resolved in a way that could compromise data integrity.
- High-integrity actions (official result validation, medal tally finalization) should have clear rules for how offline-captured data is reconciled before being treated as official.

**Detailed offline/synchronization architecture is a later-phase (architecture) concern.** This section establishes the principle, not the mechanism.

## 18. Separation of Duties

Consistent with the Product Principles, PMMS's operating model should ensure:

- The role that enters a result is not the sole authority that validates it as official.
- The role that manages eligibility submission is not the sole authority that approves eligibility.
- Administrative configuration access is distinguishable from operational/field access.

The precise role/permission model implementing separation of duties is deferred to Phase 0.3.

---

## Items Requiring Confirmation (Summary)

- Concurrent vs. sequential meet support at launch.
- Formal title/authority of the Meet Director/Administrator role within DepEd governance.
- Canonical committee list for a typical provincial meet.
- Delegation structure and grouping rules.
- Roles authorized to enter vs. validate results, per sport.
- Authoritative escalation and appeals chain per DepEd/sport governance.
- Data stewardship assignments per data category.
- Records retention duration, format, and legal basis.

These items are also tracked in [open-decisions.md](open-decisions.md) where they carry a decision ID.
