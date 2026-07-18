# PMMS Permission Catalog

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [role-catalog.md](role-catalog.md) · [scope-model.md](scope-model.md) · [separation-of-duties-matrix.md](separation-of-duties-matrix.md)

Permissions represent **meaningful business actions**, not raw CRUD operations (working rule 17). Naming follows `resource.action` in kebab-case (see [phase-0.3, Section 9](phase-0.3-access-and-assignment-architecture.md#9-permission-naming-standard)). This catalog is architecturally complete for high-value and high-risk operations — it is not an exhaustive enumeration of every possible field-level permission, which is an implementation-phase concern.

**Risk level legend:** `Low` — limited blast radius, easily corrected. `Medium` — meaningful operational impact. `High` — affects institutional trust or sensitive data. `Critical` — high-integrity domain action per [high-integrity-domain-rules.md](high-integrity-domain-rules.md).

**Audit level legend:** `Standard` — actor + timestamp. `Elevated` — actor + timestamp + reason. `Critical` — actor + timestamp + reason + evidence retention + immutable history.

---

## Administration (BC-01, BC-02, BC-32, BC-34)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `organization.manage` | Configure organization hierarchy | Organization | manage | High | ROLE-06 | Organization | Yes | None | — | Elevated | No | Recommended |
| `platform.configure` | Configure platform-wide settings | Platform | configure | High | ROLE-01, ROLE-02 | Platform | No | None | — | Elevated | No | Recommended |
| `reference-data.manage` | Manage shared enumerations/reference values | Reference Data | manage | Medium | ROLE-02 | Platform | No | Versioned, not in-place edit | — | Elevated | No | Recommended |
| `user-account.manage` | Create/suspend/revoke user accounts | User Account | manage | High | ROLE-01, ROLE-02 | Platform/Organization | No | Account status | SOD-08 (not same as Audit Viewer) | Elevated | No | Recommended |
| `security-event.review` | Review security event log | Security Event | review | High | ROLE-03 | Platform | No | None | SOD-08 | Elevated | No | Recommended |
| `audit-event.view` | View audit records | Audit Event | view | High | ROLE-05, ROLE-15 | Platform/Meet | Optional | None | SOD-08 | Standard | No | Recommended |
| `audit-event.export` | Export audit records | Audit Event | export | Critical | ROLE-05 | Platform | No | None | SOD-08 | Critical | No | Recommended |

## Meet Lifecycle (BC-04)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `meet.create` | Create a new meet | Meet | create | Medium | ROLE-10 | Organization | No | None | — | Elevated | No | Recommended |
| `meet.update` | Update meet configuration | Meet | update | Medium | ROLE-10, ROLE-11 | Meet | Yes | Meet not closed | — | Standard | No | Recommended |
| `meet.activate` | Activate a meet | Meet | activate | High | ROLE-10 | Meet | Yes | Config complete | — | Elevated | No | Recommended |
| `meet.suspend` | Suspend meet operations | Meet | suspend | High | ROLE-11 | Meet | Yes | Meet active | — | Elevated | No | Recommended |
| `meet.reopen` | Resume a suspended meet | Meet | reopen | High | ROLE-11 | Meet | Yes | Meet suspended | — | Elevated | No | Recommended |
| `meet.close` | Close a meet | Meet | close | Critical | ROLE-10 | Meet | Yes | No open protests, tally published | — | Critical | No | Recommended |
| `meet.archive` | Archive a closed meet | Meet | archive | Medium | ROLE-10 | Meet | Yes | Meet closed | — | Elevated | No | Recommended |

## Committee Operations (BC-05)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `committee.create` | Establish a committee | Committee | create | Medium | ROLE-10, ROLE-11 | Meet | Yes | None | — | Standard | No | Recommended |
| `committee.assign-member` | Assign a member to a committee | Committee | assign-member | Medium | ROLE-36 | Meet + Committee | Yes | None | — | Standard | No | Recommended |
| `committee-task.record` | Record a committee task/deliverable | Committee Task | record | Low | ROLE-37 | Committee | Yes | None | — | Standard | Yes (append-only) | Recommended |
| `committee-report.submit` | Submit a committee report | Committee Report | submit | Medium | ROLE-36 | Committee | Yes | None | — | Standard | No | Recommended |
| `committee-deliverable.approve` | Approve a committee deliverable | Committee Deliverable | approve | Medium | ROLE-36, ROLE-11 | Committee | Yes | Deliverable submitted | — | Elevated | No | Recommended |

## Registration (BC-08)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `athlete-registration.create` | Create a draft registration | Athlete Registration | create | Low | ROLE-17, ROLE-50 | Delegation | Yes | Registration window open | — | Standard | No | Recommended |
| `athlete-registration.submit` | Submit a registration for review | Athlete Registration | submit | Medium | ROLE-17, ROLE-50 | Delegation | Yes | Draft complete | SOD-01 (not self-review) | Standard | No | Recommended |
| `athlete-registration.review` | Review a submitted registration | Athlete Registration | review | Medium | ROLE-18 | Committee | Yes | Submitted | SOD-01 | Standard | No | Recommended |
| `athlete-registration.return` | Return registration for correction | Athlete Registration | return | Medium | ROLE-18 | Committee | Yes | Submitted | — | Standard | No | Recommended |
| `athlete-registration.withdraw` | Withdraw a registration | Athlete Registration | withdraw | Medium | ROLE-49, ROLE-50 | Delegation | Yes | Not locked | — | Standard | No | Recommended |
| `athlete-registration.lock` | Lock registration (post-deadline) | Athlete Registration | lock | High | ROLE-13 | Meet | Yes | Deadline reached | — | Elevated | No | Recommended |

## Eligibility *(High-Integrity)* (BC-09)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `eligibility-requirement.view` | View eligibility requirement definitions | Eligibility Requirement | view | Low | ROLE-17, ROLE-49 | Meet | No | None | — | Standard | Yes | Recommended |
| `eligibility-case.review` | Review submitted evidence | Eligibility Case | review | High | ROLE-19 | Delegation (assigned) | Yes | Submitted | SOD-01 | Elevated | No | Recommended |
| `eligibility-case.record-finding` | Record a review finding | Eligibility Case | record-finding | High | ROLE-19 | Delegation (assigned) | Yes | Under review | SOD-01 | Elevated | No | Recommended |
| `eligibility-case.view-restricted-evidence` | View restricted supporting evidence | Eligibility Case | view-restricted-evidence | Critical | ROLE-19, ROLE-20 | Delegation (assigned) | Yes | None | — | Critical | No | Recommended |
| `eligibility-case.approve` | Approve eligibility | Eligibility Case | approve | **Critical** | ROLE-20 | Delegation (assigned) | Yes | Reviewed, evidence complete | **SOD-01 (critical)** | Critical | **No — never offline** | Blocking (OD-07) |
| `eligibility-case.reject` | Reject eligibility | Eligibility Case | reject | Critical | ROLE-20 | Delegation (assigned) | Yes | Reviewed | SOD-01 | Critical | No | Blocking (OD-07) |
| `eligibility-case.reopen` | Reopen a decided case | Eligibility Case | reopen | Critical | ROLE-20 (elevated) | Delegation (assigned) | Yes | Decided | SOD-01 | Critical | No | Blocking (OD-07) |
| `eligibility-case.view-medical-status` | View medical clearance status flag only | Eligibility Case | view-medical-status | High | ROLE-21 | Delegation (assigned) | Yes | None | ACL boundary — never raw medical data | Elevated | No | Requires validation (DD-08) |

## Competition Entries (BC-11)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `competition-entry.submit` | Submit an entry | Competition Entry | submit | Medium | ROLE-50 | Delegation + Sport | Yes | Athlete cleared | Requires `eligibility-case.approve`d state | Standard | No | Recommended |
| `competition-entry.review` | Review submitted entries | Competition Entry | review | Medium | ROLE-24 | Sport | Yes | Submitted | — | Standard | No | Recommended |
| `competition-entry.substitute` | Substitute a participant | Competition Entry | substitute | Medium | ROLE-50 | Delegation + Sport | Yes | Not locked | Sport-rule dependent | Standard | No | Recommended |
| `competition-entry.withdraw` | Withdraw an entry | Competition Entry | withdraw | Medium | ROLE-49, ROLE-50 | Delegation + Sport | Yes | Not locked | — | Standard | No | Recommended |
| `competition-entry.lock` | Lock entries for an event | Competition Entry | lock | High | ROLE-24 | Sport | Yes | Entry window closed | — | Elevated | No | Recommended |

## Tournament Management *(High-Integrity — progression)* (BC-12)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `tournament.configure-format` | Configure competition format | Tournament | configure-format | High | ROLE-24 | Sport | Yes | Sourced from BC-10 | — | Elevated | No | Recommended |
| `tournament.generate-draw` | Generate a draw/bracket/pool | Tournament | generate-draw | High | ROLE-24 | Sport | Yes | Entries locked | — | Elevated | No | Recommended |
| `tournament.approve-draw` | Approve a generated draw | Tournament | approve-draw | High | ROLE-24, ROLE-26 | Sport | Yes | Draw generated | Distinct from generator recommended | Elevated | No | Requires validation |
| `match.schedule` | Assign a match/heat to venue/time | Match | schedule | Medium | ROLE-24 | Sport + Venue | Yes | Draw approved | — | Standard | No | Recommended |
| `match.reschedule` | Change a match's schedule slot | Match | reschedule | Medium | ROLE-24 | Sport + Venue | Yes | Not started | — | Elevated | No | Recommended |
| `competitor.advance` | Advance a competitor to next round | Competitor | advance | High | System-derived from `official-result.certify` | Sport | Yes | Result certified | Must derive from certified result | Elevated | No | Recommended |
| `progression.override` | Manually override advancement | Progression | override | Critical | ROLE-26 | Sport | Yes | Exceptional only | SOD-03 | Critical | No | Requires validation |

## Technical Officials (BC-13)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `official-profile.manage` | Manage own official profile/qualifications | Official Profile | manage | Low | ROLE-27 | Self | No | None | — | Standard | No | Recommended |
| `official-assignment.create` | Assign an official to a match/venue | Official Assignment | create | Medium | ROLE-24, ROLE-26 | Sport + Venue | Yes | No conflict declared | — | Standard | Yes | Recommended |
| `official-assignment.accept` | Accept an assignment | Official Assignment | accept | Low | ROLE-27 | Self | No | Proposed | — | Standard | Yes | Recommended |
| `official-assignment.declare-conflict` | Declare a conflict of interest | Official Assignment | declare-conflict | Medium | ROLE-27 | Self | No | None | Feeds SOD-03 | Elevated | Yes | Recommended |
| `official-assignment.replace` | Replace an assigned official | Official Assignment | replace | Medium | ROLE-24, ROLE-26 | Sport | Yes | None | — | Standard | No | Recommended |
| `official-attendance.record` | Record official attendance | Official Assignment | record-attendance | Low | ROLE-24 | Sport + Venue | Yes | None | — | Standard | Yes | Recommended |

## Scoring and Results *(High-Integrity)* (BC-15, BC-16)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `score-record.submit` | Capture a raw score | Score Record | submit | High | ROLE-27 (Scorer/Timer function) | Event/Match | Yes | Match in progress/concluded | — | Critical | **Yes — primary offline capture point** | Recommended |
| `score-record.correct` | Correct a captured score | Score Record | correct | High | ROLE-27 (same entering official, versioned) | Event/Match | Yes | Not yet validated | Original value retained | Critical | Yes | Recommended |
| `score-record.validate` | Validate a captured score | Score Record | validate | Critical | ROLE-28 | Event/Match | Yes | Captured | **SOD-02 (validator ≠ enterer)** | Critical | **No — never offline** | Recommended |
| `official-result.certify` | Certify an official result | Official Result | certify | **Critical** | ROLE-29 | Sport + Event | Yes | All scores validated | **SOD-02, SOD-03** | Critical | No | Blocking (OD-08) |
| `official-result.supersede` | Correct a certified result via versioned supersession | Official Result | supersede | Critical | ROLE-29 (or via ROLE-26 protest resolution) | Sport + Event | Yes | Certified, correction warranted | Distinct from original certifier where a protest is involved | Critical | No | Blocking (OD-08, DD-15) |
| `official-result.publish` | Publish a certified result | Official Result | publish | Critical | ROLE-33 | Sport + Meet | Yes | Certified, no active hold | Distinct from certifier recommended (DD-17) | Critical | No | Recommended |
| `official-result.unpublish` | Withdraw a published result (exceptional) | Official Result | unpublish | Critical | ROLE-33 (with ROLE-10 approval) | Meet | Yes | Exceptional only | Elevated approval | Critical | No | Requires validation |

## Protest and Appeals *(High-Integrity)* (BC-17)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `protest.file` | File a protest | Protest | file | Medium | ROLE-49, ROLE-50 (authorized filer) | Delegation | Yes | Within filing deadline | — | Elevated | No | Requires validation (OD-09) |
| `protest.review` | Review a filed protest | Protest | review | High | ROLE-26 | Sport | Yes | Filed | Distinct from result certifier if same result (SOD-03) | Elevated | No | Blocking (OD-09) |
| `official-result.place-on-hold` | Place a result on hold pending protest | Official Result | place-on-hold | High | System-triggered by `protest.file` | Sport | — | Protest filed | — | Elevated | No | Recommended |
| `protest.resolve` | Resolve a protest | Protest | resolve | **Critical** | ROLE-26 | Sport | Yes | Reviewed | **SOD-03** | Critical | No | Blocking (OD-09) |
| `official-result.release-hold` | Release a result hold | Official Result | release-hold | High | ROLE-26 | Sport | Yes | Protest resolved | — | Elevated | No | Blocking (OD-09) |
| `appeal.file` | File an appeal of a protest decision | Appeal | file | Medium | ROLE-49 | Delegation | Yes | Within appeal window | — | Elevated | No | Requires validation |
| `appeal.resolve` | Resolve an appeal | Appeal | resolve | Critical | Higher authority than ROLE-26 (TBD) | Sport/Meet | Yes | Reviewed | Distinct from original protest resolver | Critical | No | Blocking (OD-09) |

## Medal Tally *(High-Integrity, derived)* (BC-18)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `medal-tally.view-provisional` | View provisional (unpublished) tally | Medal Tally | view-provisional | Medium | ROLE-30, ROLE-31, ROLE-32, ROLE-10 | Meet | Yes | None | — | Standard | No | Recommended |
| `medal-tally.recalculate` | Trigger tally recalculation | Medal Tally | recalculate | High | ROLE-30 | Meet | Yes | Certified results only as source | Never from raw scores | Elevated | No | Recommended |
| `medal-tally.review` | Review a recalculated tally | Medal Tally | review | High | ROLE-31 | Meet | Yes | Recalculated | SOD-04 | Elevated | No | Recommended |
| `medal-tally.certify` | Certify the tally | Medal Tally | certify | **Critical** | ROLE-32 | Meet | Yes | Reviewed | **SOD-04** | Critical | No | Blocking (OD-12) |
| `medal-tally.publish` | Publish the certified tally | Medal Tally | publish | Critical | ROLE-33 | Meet | Yes | Certified | Elevated confirmation for post-publication corrections (DD-16) | Critical | No | Recommended |

## Accreditation and Access *(High-Integrity)* (BC-19, BC-20)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `accreditation-credential.approve` | Approve an accreditation request | Accreditation Credential | approve | High | ROLE-47 | Committee | Yes | Identity resolved, eligibility cleared where required | — | Elevated | No | Recommended |
| `accreditation-credential.issue` | Issue a credential | Accreditation Credential | issue | High | ROLE-47 | Committee | Yes | Approved | — | Elevated | No | Recommended |
| `accreditation-credential.print` | Print/reprint a credential | Accreditation Credential | print | Medium | ROLE-47 | Committee | Yes | Issued | — | Standard | No | Recommended |
| `accreditation-credential.replace` | Replace a lost/damaged credential | Accreditation Credential | replace | Medium | ROLE-47 | Committee | Yes | Issued | Reason required | Elevated | No | Recommended |
| `accreditation-credential.revoke` | Revoke a credential | Accreditation Credential | revoke | **High** | ROLE-47, ROLE-43 (security escalation) | Committee | Yes | Issued | — | Elevated | No — but propagation to scanners is highest sync priority | Recommended |
| `access-scan.validate` | Perform a scan validation | Access Scan | validate | Medium | ROLE-48 | Venue + Device + Shift | Yes | Cached credential set available | — | Standard | **Yes — critical offline priority** | Recommended |
| `access-scan.override` | Manually override a denied scan | Access Scan | override | **High** | ROLE-43 (not ROLE-48) | Venue | Yes | Denial occurred | **SOD-05** | Critical | Provisional only, requires later confirmation | Recommended |
| `access-log.view` | View access scan logs | Access Scan | view | Medium | ROLE-43 | Venue/Meet | Yes | None | — | Elevated | No | Recommended |

## Medical *(High-Integrity, Restricted)* (BC-21)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `medical-encounter.record` | Record a medical encounter | Medical Encounter | record | High | ROLE-38, ROLE-39 | Venue + Shift | Yes | None | — | Critical | **Yes — must function in emergencies** | Recommended |
| `medical-encounter.update` | Update an encounter record | Medical Encounter | update | High | ROLE-38, ROLE-39 | Venue + Shift | Yes | Recorded | Correction visible, not silent | Critical | Yes | Recommended |
| `medical-encounter.view-sensitive` | View full clinical detail | Medical Encounter | view-sensitive | **Critical** | ROLE-38 | Own committee only | Yes | None | Need-to-know | Critical | No | Blocking (OD-15) |
| `medical-incident.report` | Report a medical incident | Medical Incident | report | High | ROLE-38, ROLE-39 | Venue + Shift | Yes | None | — | Critical | Yes | Recommended |
| `fitness-status.issue` | Issue an athlete fitness status | Fitness Status | issue | Critical | ROLE-38 | Venue | Yes | Encounter recorded | — | Critical | No | Recommended |
| `medical-report.export` | Export a medical report | Medical Report | export | **Critical** | ROLE-38 (with approval) | Committee | Yes | None | SOD-09 | Critical | No | Blocking (OD-15) |

## Logistics (BC-22, BC-23, BC-24)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `billeting-assignment.create` | Assign a delegation to accommodation | Billeting Assignment | create | Low | ROLE-40 | Committee | Yes | Capacity available | — | Standard | No | Recommended |
| `billeting-assignment.check-in` | Check in a delegation | Billeting Assignment | check-in | Low | ROLE-40 | Venue | Yes | Assigned | — | Standard | Yes | Recommended |
| `meal-entitlement.validate` | Validate a meal entitlement at distribution | Meal Entitlement | validate | Low | ROLE-41 | Venue | Yes | Entitlement issued | — | Standard | **Yes — high offline relevance** | Recommended |
| `transport-trip.dispatch` | Dispatch a transport trip | Transport Trip | dispatch | Low | ROLE-42 | Committee | Yes | Vehicle/driver available | — | Standard | Yes | Recommended |
| `logistics-incident.record` | Record a logistics incident | Logistics Incident | record | Medium | ROLE-40, ROLE-41, ROLE-42 | Committee | Yes | None | — | Elevated | Yes | Recommended |

## Finance (BC-26)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `budget-allocation.create` | Create a budget allocation | Budget Allocation | create | Medium | ROLE-45 (staff) | Committee | Yes | None | SOD-06 | Elevated | No | Recommended |
| `budget-allocation.approve` | Approve a budget allocation | Budget Allocation | approve | High | ROLE-45 (head) | Committee | Yes | Created | **SOD-06** | Elevated | No | Recommended |
| `expense.record` | Record an expense | Expense | record | Medium | ROLE-45 (staff) | Committee | Yes | None | SOD-06 | Elevated | No | Recommended |
| `expense.review` | Review a recorded expense | Expense | review | Medium | ROLE-45 (head) | Committee | Yes | Recorded | **SOD-06** | Elevated | No | Recommended |
| `finance-record.view-restricted` | View restricted supporting documents | Finance Record | view-restricted | High | ROLE-45 | Committee | Yes | None | — | Critical | No | Recommended |

## Public Information and Media (BC-28, BC-29)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `announcement.draft` | Draft an announcement | Announcement | draft | Low | ROLE-35 | Committee | Yes | None | — | Standard | No | Recommended |
| `announcement.review` | Review a draft announcement | Announcement | review | Medium | ROLE-46 | Committee | Yes | Drafted | Distinct from drafter recommended | Standard | No | Recommended |
| `announcement.publish` | Publish an announcement publicly | Announcement | publish | High | ROLE-34 | Committee | Yes | Reviewed | — | Elevated | No | Recommended |
| `schedule.publish` | Publish the public schedule | Schedule | publish | High | ROLE-34 | Meet | Yes | Finalized | — | Elevated | No | Recommended |
| `public-content.view` | View public-approved content | Public Content | view | None | Public (no account) | Public | No | Published only | — | None | N/A | Recommended |

## Records and Reporting (BC-30, BC-33)

| Permission Key | Business Meaning | Resource | Action | Risk | Required Role Candidates | Required Scope | Required Assignment | Resource-State Conditions | SoD | Audit | Offline | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `document.upload` | Upload a supporting document | Document | upload | Low | Multiple (context-dependent) | Context-dependent | Yes | None | — | Standard | No | Recommended |
| `document.verify` | Verify document authenticity | Document | verify | Medium | ROLE-22 | Committee | Yes | Uploaded | — | Standard | No | Recommended |
| `document.classify` | Set/change document access classification | Document | classify | High | ROLE-13 | Committee | Yes | None | — | Elevated | No | Recommended |
| `report.generate` | Generate an operational report | Report | generate | Low | Multiple (per committee) | Committee/Meet | Yes | None | — | Standard | No | Recommended |
| `report.export` | Export a report | Report | export | Medium | Multiple (per committee) | Committee/Meet | Yes | None | — | Elevated | No | Recommended |

---

## Naming Standard Applied

- Lowercase, kebab-case for compound resources and actions (e.g., `official-result.certify`, `athlete-registration.review`).
- Stable business terminology from [domain-glossary.md](domain-glossary.md), never UI labels or route names.
- No generic `manage-all` or wildcard permission granted to any ordinary (non-Platform-Super-Administrator) role.
- `view-sensitive` / `view-restricted` are always separate permissions from the corresponding `view` / `view-summary` permission, never bundled.
- `approve`, `certify`, `publish`, `override`, `reopen`, `revoke`, and `delete` (where it exists at all — most high-integrity domains use `withdraw`/`archive` instead of true deletion) are always distinct permissions, never folded into a generic `edit`.
