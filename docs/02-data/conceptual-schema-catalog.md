# PMMS Conceptual Schema Catalog

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [logical-data-architecture.md](logical-data-architecture.md) · [persistence-ownership-map.md](persistence-ownership-map.md) · [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md)

This document defines 34 conceptual schema groups (one per bounded context) and, within them, the major aggregate persistence candidates. **No physical table, column list, or migration is created here** — schema groups are a planning grouping, not a database schema.

---

## Part 1 — Conceptual Schema Groups

| Group | Owning Context | Key Aggregate Candidates | High-Integrity | Sensitivity | History Requirement | Public Exposure | Offline Relevance | Expected Growth | Open Questions |
|---|---|---|---|---|---|---|---|---|---|
| Platform and tenancy | BC-01 | `PlatformSettings`, `FeatureFlag` | No | Low | Low | None | None | Low volume | Tenant-column timing ([data-open-decisions.md](data-open-decisions.md)) |
| Identity and accounts | BC-02 | `UserAccount`, `Credential`, `Session` | No (security-critical) | High | High (security audit) | None | Limited | Moderate | MFA-related storage specifics |
| Organizations and directories | BC-03 | `Organization`, `OrganizationHierarchyNode` | No | Low | Medium | Low | Low | Low volume | Data source (OD-06) |
| Authorization and assignments | BC-02/03 (cross-cutting) | `RoleAssignment` (per [assignment-model.md](../01-architecture/assignment-model.md)) | No | Medium | High (accountability) | None | Cached snapshot | Moderate | See [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) for assignment history modeling |
| Meet administration | BC-04 | `Meet` | Yes | Low | High | Medium | Low | Low volume (one row per meet) | Concurrent-meet support (Phase 0.1 OD-01) |
| Committees and operations | BC-05 | `Committee`, `CommitteeMembership` | No | Low | Medium | None | Low–Medium | Low–moderate | Committee canonical list |
| Delegations | BC-06 | `Delegation`, `DelegationMembership` | No | Low–Medium | Medium | Low | Low | Moderate | Delegation hierarchy (Phase 0.1 OD-04) |
| People and participant profiles | BC-07 | `Participant` | Yes | High (PII) | High | None | Low | High volume (grows every meet cycle) | Registry model (DD-01) |
| Athlete registrations | BC-08 | `AthleteRegistration` | Yes | Medium | Medium | None | Low | High volume (per meet) | Guardian consent (Phase 0.1 OD-16) |
| Eligibility and clearances | BC-09 | `EligibilityCase` | **Critical** | High | **Critical** | None | None | High volume (per meet) | **Blocking — Phase 0.1 OD-07** |
| Sports catalog and rule references | BC-10 | `SportDefinition`, `EventDefinition` | Yes | Low | High (versioned) | Medium | Low | Low volume, high stability | **Blocking — Phase 0.1 OD-10** |
| Competition entries | BC-11 | `CompetitionEntry` | Yes | Low–Medium | Medium | Low | Low | High volume (per meet) | Multi-sport participation (DD-11) |
| Tournament structures | BC-12 | `Tournament`, `Match`, `Heat` | **Critical** | Low | High | Medium–High | Medium | High volume (per meet) | Format configurability (DD-13) |
| Technical officials and assignments | BC-13 | `OfficialAssignment` | Yes | Low–Medium | Medium | Low | Medium | Moderate | Identity ownership (DD-02) |
| Venues and schedules | BC-14 | `Venue`, `Schedule` | Yes | Low | Medium | High | Medium | Moderate | Boundary vs. Tournament (DD-07) |
| Scoring | BC-15 | `ScoreRecord` | **Critical** | Medium | **Critical** | None | **Critical** | Very high volume (every attempt/heat) | Scoring model per sport (OD-10) |
| Official results | BC-16 | `OfficialResult` | **Critical** | Low | **Critical** | High | None | High volume | **Blocking — Phase 0.1 OD-08** |
| Protests and appeals | BC-17 | `ProtestCase` | **Critical** | Medium | **Critical** | Low | Low | Low–moderate volume | **Blocking — Phase 0.1 OD-09** |
| Medals and standings | BC-18 | `MedalAward`, `TeamStanding` | **Critical (derived)** | Low | **Critical** | High | None | Moderate volume | **Blocking — Phase 0.1 OD-12/OD-13** |
| Accreditation | BC-19 | `AccreditationCredential` | **Critical** | Medium | High | None | Low | High volume (per meet, per participant) | Coverage scope (Phase 0.1 OD-14) |
| Access validation | BC-20 | `AccessScan` | High | Medium | High | None | **Critical** | Very high volume (every scan) | QR validation rules (Phase 0.1 OD-28) |
| Medical operations | BC-21 | `MedicalEncounter` | Yes | **Critical** | **Critical** | None | High | Moderate volume | **Blocking — Phase 0.1 OD-15** |
| Billeting | BC-22 | `BilletingAssignment` | No | Low | Low | None | Medium | Moderate | None blocking |
| Food services | BC-23 | `MealEntitlement` | No | Low | Low | None | Medium–High | High volume (every meal) | None blocking |
| Transportation | BC-24 | `TransportTrip` | No | Low | Low | Low | Medium | Moderate | None blocking |
| Security | BC-25 | `SecurityIncident` | Yes (safety) | Medium–High | High | None | High | Low–moderate | None blocking |
| Finance | BC-26 | `BudgetAllocation`, `Expense` | Yes | Medium | High | None | Low | Low–moderate | Integration scope |
| ICT support | BC-27 | `SupportTicket` | No | Low | Medium | None | Medium | Moderate | None blocking |
| Media and communications | BC-28 | `Announcement` | Standard–High | Low | Medium | High | Low | Moderate | Media accreditation process |
| Public publication | BC-29 | `PublicationItem` (projection only) | No | Low | Low (regenerable) | **High** | N/A | High read volume | Public athlete-profile limits (Phase 0.1 OD-17) |
| Documents and files | BC-30 | `DocumentRecord` | Yes | Varies | High | Low | Low | High volume (every upload) | Retention policy (OD-24, DD-23) |
| Notifications | BC-31 | `Notification` | No | Low–Medium | Low | N/A | Low | Very high volume (every triggering event) | Channel providers (RD-05) |
| Audit and compliance | BC-32 | `AuditEvent` | **Critical** | Medium–High | **Critical (immutable)** | None | Low (buffered) | Very high volume (every consequential action) | Retention (OD-24) |
| Integrations | (cross-cutting, no context owns integration data yet) | — | N/A | N/A | N/A | N/A | N/A | N/A | No integration currently approved |
| Offline synchronization | (cross-cutting) | `SynchronizationBatch` | No (mechanism, not business data) | Medium | Medium | N/A | **Critical (this is the mechanism)** | High volume | See [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md) |
| Reporting projections | BC-33 | (read models only) | No | Low | Low (regenerable) | Low | Low | High read volume | Data warehouse timing (DD-25) |

**Configuration and Reference Data (BC-34)** is intentionally cross-cutting rather than listed as a single row — its versioned reference values are consumed by nearly every group above (per [context-map.md, "Why Shared Kernel Is Avoided"](../01-architecture/context-map.md#why-shared-kernel-is-avoided)); see [database-naming-and-design-standards.md](database-naming-and-design-standards.md) for how status/reference vocabularies are persisted.

---

## Part 2 — Aggregate Persistence Boundaries

| Aggregate | Owning Context | Persistence Responsibility | Transaction Boundary | Internal Child Concepts | External References | History | Versioning | Deletion Behavior | Audit | Offline | Volume | Validation Needs |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `Organization` | BC-03 | BC-03 | Single-row | Hierarchy node metadata | — | Medium | No | Deactivate, never hard-delete | Standard | Low | Low | DD-09 |
| `Meet` | BC-04 | BC-04 | Single-row + lifecycle | Configuration | Organization | High | No (lifecycle state, not versioned content) | Archive, never delete | Elevated | Low | Low | Concurrent-meet support |
| `Committee` | BC-05 | BC-05 | Committee + membership together | Membership rows | Meet, User Account | Medium | No | Deactivate | Standard | Low–Medium | Low–Moderate | Committee list |
| `Delegation` | BC-06 | BC-06 | Delegation + roster | Roster membership | Organization, Meet | Medium | No | Deactivate | Standard | Low | Moderate | Hierarchy (OD-04) |
| `Participant` | BC-07 | BC-07 | Single participant + identity-correction history | Identity-correction entries | — | High | Yes (identity corrections versioned) | Never hard-delete; merge/archive only | High | Low | High | DD-01 |
| `AthleteRegistration` | BC-08 | BC-08 | Registration submission | — | Participant, Delegation, Meet | Medium | No (state machine, not content versioning) | Withdraw, never delete | Standard | Low | High | Guardian consent |
| `EligibilityCase` | BC-09 | BC-09 | Case + decision, strongly consistent | Findings, evidence references | Registration, Document | **Critical** | Yes (decision history) | Never delete; reopen/reverse only | **Critical** | None | High | **OD-07** |
| `SportDefinition` / `EventDefinition` | BC-10 | BC-10 | Per-version immutable | — | — | High | **Yes — explicit version identity** | Deprecate, never delete a published version | Standard | Low | Low | **OD-10** |
| `CompetitionEntry` | BC-11 | BC-11 | Entry submission | Substitution history | Registration, Eligibility, Event | Medium | No | Withdraw, never delete | Standard | Low | High | DD-11, DD-12 |
| `Tournament` | BC-12 | BC-12 | Structure + progression, strongly consistent | Draw, seeding | Entries, Event | High | Yes (draw regeneration versioned pre-publication) | Never delete once published | Elevated | Medium | High | DD-13 |
| `Match` / `Heat` | BC-12 | BC-12 | Per competition unit | — | Officials, Venue/Schedule | Medium | No | Never delete | Standard | Medium | Very High | None blocking |
| `OfficialAssignment` | BC-13 | BC-13 | Assignment record | Acceptance, conflict declaration | Participant, Match | Medium | No | Replace, never delete | Standard | Medium | Moderate | DD-02 |
| `Schedule` | BC-14 | BC-14 | Slot allocation | Revision history | Venue, Match | Medium | Yes (revisions superseded, not overwritten) | Never delete | Standard | Medium | Moderate | DD-07 |
| `ScoreRecord` | BC-15 | BC-15 | Per score entry, offline-tolerant | Correction history | Match, Official | **Critical** | Yes (corrections versioned) | Never delete or overwrite | **Critical** | **Critical** | Very High | OD-10 |
| `OfficialResult` | BC-16 | BC-16 | Result assembly, strongly consistent | — | Score Records (via ACL) | **Critical** | **Yes — explicit version, supersession chain** | Never delete | **Critical** | None | High | **OD-08** |
| `ProtestCase` | BC-17 | BC-17 | Case + evidence + decision | Evidence references | Official Result | **Critical** | Yes (decision history) | Never delete | **Critical** | Low | Low–Moderate | **OD-09** |
| `MedalAward` / `TeamStanding` | BC-18 | BC-18 | Per snapshot, strongly consistent | — | Official Result, Delegation | **Critical** | **Yes — versioned snapshots, all retained** | Never delete a published snapshot | **Critical** | None | Moderate | **OD-12/OD-13** |
| `AccreditationCredential` | BC-19 | BC-19 | Credential lifecycle | Replacement history | Participant, Eligibility | High | No (status machine + replacement chain) | Revoke, never delete | Elevated | Low | High | OD-14 |
| `AccessScan` | BC-20 | BC-20 | Per scan, offline-tolerant, append-only | — | Credential (cached) | High | No (append-only) | Never delete | Elevated | **Critical** | Very High | OD-28 |
| `MedicalEncounter` | BC-21 | BC-21 | Per encounter, offline-tolerant | Treatment/referral notes | Participant | **Critical** | Yes (amendments versioned) | Never delete | **Critical** | High | Moderate | **OD-15** |
| `BilletingAssignment` | BC-22 | BC-22 | Assignment record | — | Delegation | Low | No | Reassign, soft-deletable | Standard | Medium | Moderate | None |
| `MealEntitlement` | BC-23 | BC-23 | Entitlement + distribution | — | Delegation, Registration | Low | No | Soft-deletable | Standard | Medium–High | High | None |
| `TransportTrip` | BC-24 | BC-24 | Trip record | — | Delegation | Low | No | Soft-deletable | Standard | Medium | Moderate | None |
| `SecurityIncident` | BC-25 | BC-25 | Incident + escalation | Escalation history | Access Validation, Venue | Medium–High | Yes (escalation state history) | Never delete | Elevated | High | Low–Moderate | None |
| `BudgetAllocation` | BC-26 | BC-26 | Allocation + approval | — | Committee, Meet | Medium | Yes (approval history) | Never delete | Elevated | Low | Low–Moderate | Integration scope |
| `SupportTicket` | BC-27 | BC-27 | Ticket record | — | Meet, Venue | Low | No | Soft-deletable | Standard | Medium | Moderate | None |
| `PublicationItem` | BC-29 | N/A — projection | N/A | — | Results, Tally, Schedule, Announcements | Low | No (regenerated) | Rebuildable | N/A | N/A | High read | DD-17 |
| `DocumentRecord` | BC-30 | BC-30 | Metadata + version history | Version chain | Any evidence-producing context | High | **Yes — explicit version chain** | Archive/retain per policy, never silently delete | Elevated | Low | High | OD-24 |
| `Notification` | BC-31 | BC-31 | Delivery record | Delivery attempt history | Any triggering context | Low | No | Soft-deletable after retention window | Standard | Low | Very High | RD-05 |
| `AuditEvent` | BC-32 | BC-32 | Single immutable event | — | All contexts (source) | **Critical (immutable)** | N/A (never versioned — append new instead) | **Never delete under any circumstance short of approved retention expiry** | **Critical (self-referential)** | Low | Very High | DD-24 |
| `SynchronizationBatch` | Cross-cutting sync mechanism | Owning device/session | Batch of pending changes | Per-item status | Device, User, Meet | Medium | No | Purge after successful reconciliation window | Elevated | **Critical (this IS the offline mechanism)** | High | See [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md) |

Aggregates marked **Critical** in the History/Versioning columns are the primary subject of [high-integrity-data-model.md](high-integrity-data-model.md) and [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md).
