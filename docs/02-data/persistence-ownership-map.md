# PMMS Persistence Ownership Map

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [logical-data-architecture.md](logical-data-architecture.md) · [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) · [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md)

For every one of the 34 approved bounded contexts, this document defines what it authoritatively persists, what it merely references, and how its data is treated for offline replication, retention, correction, and archival. **No physical table is created here** — this extends [data-ownership-map.md](../01-architecture/data-ownership-map.md) (Phase 0.2, conceptual data ownership) with persistence-specific detail.

**Column legend:** *Authoritative* = records this context owns and is the only writer of. *Referenced* = identifiers/data from other contexts this context reads but never writes. *Snapshots/Projections* = point-in-time or derived copies this context may hold for its own purposes. *Offline* = replication allowance per [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md). *Retention* = importance tier (Low/Medium/High/Critical). *Correction Authority* = who may correct authoritative records (the owning context only, via its controlled pattern).

| Context | Authoritative Records | Referenced (Other Contexts) | Snapshots/Projections | Sensitive | High-Integrity | Public | Offline | Retention | Correction Authority | Archival Responsibility |
|---|---|---|---|---|---|---|---|---|---|---|
| Platform Administration (BC-01) | Platform settings, feature flags | — | — | Low | No | No | No | Medium | BC-01 only | BC-01 |
| Identity and Access (BC-02) | User accounts, credentials, sessions | Person (BC-07, via link) | — | High | No (security-critical) | No | Limited (cached tokens) | High | BC-02 only | BC-02 |
| Organization Directory (BC-03) | Organization hierarchy | — | — | Low | No | Low | Low (cached) | Medium | BC-03 only | BC-03 |
| Meet Administration (BC-04) | Meet identity, lifecycle, configuration | Organization (BC-03) | — | Low | Yes | Medium | Low | High | BC-04 only | BC-04, coordinating full meet archive |
| Committee Operations (BC-05) | Committee, membership | Meet (BC-04), User Account (BC-02) | — | Low | No | No | Low–Medium | Medium | BC-05 only | BC-05 |
| Delegation Management (BC-06) | Delegation, roster composition | Organization (BC-03), Meet (BC-04) | — | Low | Standard–High | Low | Low | Medium | BC-06 only | BC-06 |
| Participant Registry (BC-07) | Canonical person identity | — | — | High (PII) | Yes | No | Low (identity cache) | High | BC-07 only, via identity-correction workflow | BC-07 |
| Athlete Registration (BC-08) | Meet-scoped registration | Participant (BC-07), Delegation (BC-06) | — | Medium | Yes | No | Low | Medium | BC-08 only | BC-08 |
| Eligibility and Clearance (BC-09) | Eligibility case, decision | Registration (BC-08), Document (BC-30), Medical status flag (BC-21, via ACL) | — | High | **Critical** | No | None (never offline) | High | BC-09 only, documented reversal only | BC-09 |
| Sports Catalog (BC-10) | Sport/event definitions (versioned) | — | — | Low | Yes | Medium | Low (cached, versioned) | High | BC-10 only | BC-10 |
| Competition Entries (BC-11) | Entry records | Registration (BC-08), Eligibility (BC-09), Sport (BC-10) | — | Low–Medium | Yes | Low | Low | Medium | BC-11 only | BC-11 |
| Tournament Management (BC-12) | Tournament structure, progression | Entries (BC-11), Sport (BC-10) | — | Low | **Critical** | Medium–High | Medium (venue cache) | High | BC-12 only | BC-12 |
| Technical Officials (BC-13) | Assignment records | Participant (BC-07), Tournament (BC-12) | — | Low–Medium | Yes | Low | Medium | Medium | BC-13 only | BC-13 |
| Venue and Schedule (BC-14) | Venue records, schedule slots | Meet (BC-04), Tournament (BC-12) | — | Low | Yes | High | Medium | Medium | BC-14 only | BC-14 |
| Scoring (BC-15) | Score records (raw) | Tournament (BC-12), Officials (BC-13) | — | Medium | **Critical** | No | **Critical (primary offline)** | High | BC-15 only, versioned revision | BC-15 |
| Official Results (BC-16) | Official result records (versioned) | Score Records (BC-15, via ACL) | — | Low | **Critical** | High (once published) | None | Critical | BC-16 only, versioned supersession | BC-16 |
| Protest and Appeals (BC-17) | Protest/appeal case records | Official Result (BC-16) | — | Medium | **Critical** | Low | Low | Critical | BC-17 only | BC-17 |
| Medal Tally and Team Standings (BC-18) | Medal award/tally snapshots | Official Result (BC-16), Delegation (BC-06) | — | Low | **Critical (derived)** | High | None | Critical | BC-18 only, recomputed from BC-16 | BC-18 |
| Accreditation (BC-19) | Credential records | Participant (BC-07), Eligibility (BC-09) | Cached credential-validity set (published to BC-20) | Medium | **Critical** | No | Low (issuance) | High | BC-19 only | BC-19 |
| Access Validation (BC-20) | Access scan/transaction records | Credential (BC-19, cached) | — | Medium | High | No | **Critical (primary offline)** | Medium | BC-20 only | BC-20 |
| Medical Operations (BC-21) | Medical encounter/incident records | Participant (BC-07) | — | **Critical (health data)** | Yes | No | High | High | BC-21 only | BC-21, with elevated access control |
| Billeting (BC-22) | Billeting assignment records | Delegation (BC-06) | — | Low | No | No | Medium | Low | BC-22 only | BC-22 |
| Food Services (BC-23) | Meal entitlement/distribution records | Delegation (BC-06), Registration (BC-08) | — | Low | No | No | Medium–High | Low | BC-23 only | BC-23 |
| Transportation (BC-24) | Transport trip records | Delegation (BC-06) | — | Low | No | No | Medium | Low | BC-24 only | BC-24 |
| Security Operations (BC-25) | Security incident records | Access Validation (BC-20), Venue (BC-14) | — | Medium–High | Yes (safety) | No | High | High | BC-25 only | BC-25 |
| Finance Operations (BC-26) | Budget/expense records | Committee (BC-05), Meet (BC-04) | — | Medium | Yes | No | Low | High | BC-26 only | BC-26 |
| ICT Service Operations (BC-27) | Ticket/asset records | Meet (BC-04), Venue (BC-14) | — | Low | No | No | Medium | Low | BC-27 only | BC-27 |
| Media and Communications (BC-28) | Announcement records | Security (BC-25), Meet (BC-04) | — | Low | Standard–High | High | Low | Medium | BC-28 only | BC-28 |
| Public Information (BC-29) | **None** — non-authoritative | All publishing contexts | Public projections (Results, Tally, Schedule, Announcements) | Low | No | **High (this is the surface)** | N/A | Low (regenerable) | N/A — corrections happen upstream | N/A |
| Document and Records (BC-30) | Document metadata | Any evidence-producing context | — | Varies by category | Yes | Low (approved only) | Low | High | BC-30 only | BC-30 |
| Notifications (BC-31) | Notification/delivery records | Any triggering context | — | Low–Medium | No | N/A | Low | Low | BC-31 only | BC-31 |
| Audit and Compliance (BC-32) | Audit event records (immutable) | All contexts (event source) | — | Medium–High | **Critical** | No | Low (buffered) | **Critical** | **None — append-only, superseding entries only** | BC-32 |
| Reporting and Analytics (BC-33) | **None** — read models only | All contexts | Cross-context executive projections | Low | No | Low | Low | Low (regenerable) | N/A — corrections happen upstream | N/A |
| Configuration and Reference Data (BC-34) | Shared reference/enumeration records (versioned) | — | — | Low | Standard–High | No | Low (cached) | High | BC-34 only, versioned | BC-34 |

## Notes

- **BC-29 and BC-33 own zero authoritative persistence** — their tables (once implemented) hold only rebuildable projections, never a system of record. This is the persistence-layer proof that the Phase 0.2 "non-authoritative" designation is real, not aspirational.
- **BC-32 (Audit) is the only context where "Correction Authority" is genuinely "none"** — every other high-integrity context corrects via versioned supersession *within* its own table; audit events are corrected only by a new entry referencing the old one, never an edit to the old one.
- **Retention "Critical" tier** (BC-16, BC-17, BC-18, BC-32) marks the four data categories where loss or premature deletion would be an institutional-integrity failure, not merely an inconvenience — these are the first candidates for the strictest backup/retention treatment in [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md) and [retention-archival-and-disposal.md](retention-archival-and-disposal.md).
