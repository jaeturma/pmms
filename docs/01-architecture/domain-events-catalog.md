# PMMS Domain Event Catalog

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [workflow-and-command-catalog.md](workflow-and-command-catalog.md) · [context-map.md](context-map.md)

This catalog lists conceptual domain events — significant business facts that occurred — by owning context. **These are conceptual candidates for later technical design; no Laravel events, message schemas, or payload field definitions are created here** (per working rules 5–8).

Consistency expectation legend: **Strong** = downstream consumers must treat this synchronously/immediately for correctness; **Eventual** = downstream consumers may lag briefly without compromising integrity.

## BC-04 Meet Administration

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `MeetCreated` | Meet Administrator creates a new meet | A new bounded meet exists | Meet identity, dates, host | BC-05, BC-06, BC-33 | Strong | Yes | Yes (internal) | No | Low | Duplicate creation must be prevented | Recommended |
| `MeetActivated` | Meet transitions to active/operational status | Meet-scoped operations may now proceed | Meet ID, activation timestamp | Nearly all meet-scoped contexts | Strong | Yes | Yes | Yes (public calendar) | Low | Must be exactly-once per meet | Recommended |
| `MeetClosed` | Organizing Committee closes the meet | No further operational changes permitted | Meet ID, closure timestamp | All meet-scoped contexts, BC-32 | Strong | Yes | Yes | Yes | None | Must be exactly-once | Recommended |
| `MeetArchived` | Post-closure archiving completes | Meet record moves to historical/read-only state | Meet ID, archive timestamp | BC-30, BC-33 | Eventual | Yes | No | No | None | Idempotent by design | Recommended |

## BC-05 Committee Operations

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `CommitteeCreated` | Organizing Committee establishes a committee | A functional committee now exists for the meet | Committee ID, mandate, meet reference | BC-33 | Eventual | Yes | No | No | Low | Low | Recommended |
| `CommitteeMemberAssigned` | A member is added to a committee | Membership and accountability established | Committee ID, participant reference | BC-21, BC-22, BC-23, BC-24, BC-25, BC-26, BC-27 | Eventual | Yes | Yes | No | Low | Reassignment must not duplicate membership | Recommended |

## BC-06 Delegation Management

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `DelegationRegistered` | A school/grouping registers as a delegation | A delegation exists for the meet | Delegation ID, organization reference, meet reference | BC-08, BC-33 | Eventual | Yes | Yes | Low | Low | Duplicate registration for same org/meet must be detected | Recommended |
| `DelegationConfirmed` | Organizing Committee confirms delegation participation | Delegation is officially participating | Delegation ID, confirmation timestamp | BC-18, BC-22, BC-23, BC-24 | Eventual | Yes | Yes | Low | Low | Low | Recommended |

## BC-07 Participant Registry

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `ParticipantCreated` | A new person identity is established | A canonical participant identity now exists | Participant ID, biographical reference | BC-08, BC-13, BC-19, BC-21 | Eventual | Yes | No | No | Low | **High** — must not fire for what is actually a duplicate; ties directly to RSK-02 | Recommended |
| `ParticipantMatched` | Duplicate-detection logic (advisory) suggests or confirms a match between records | Two records are believed to represent the same person | Participant IDs (candidate pair), confidence basis | BC-07 internal review queue | Eventual | Yes | Yes (to reviewer) | No | Low | Must not auto-merge — human review required (AI advisory only) | Recommended |
| `ParticipantIdentityCorrected` | A reviewer corrects biographical or identity data | Identity record updated with attribution | Participant ID, correction reason, actor | Downstream identity consumers | Eventual | Yes | No | No | Low | Correction must be versioned, not silently overwritten | Recommended |

## BC-08 Athlete Registration

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `AthleteRegistered` | Coach/delegation submits an athlete registration | An athlete has a meet-scoped registration record | Registration ID, participant ref, meet ref, delegation ref | BC-09 | Eventual | Yes | Yes | No | Low | Low | Recommended |
| `RegistrationSubmitted` | All required registration data submitted | Registration is ready for review | Registration ID, submission timestamp | BC-09 | Eventual | Yes | Yes | No | Low | Low | Recommended |
| `RegistrationWithdrawn` | Coach/delegation withdraws a registration | Registration no longer active | Registration ID, withdrawal reason | BC-09, BC-11 | Eventual | Yes | Yes | No | Low | Must cascade withdrawal awareness to dependent entries | Recommended |

## BC-09 Eligibility and Clearance *(High-Integrity)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `EligibilityRequirementsSubmitted` | Required documents/evidence submitted | Eligibility case is ready for review | Case ID, evidence references | BC-30 | Eventual | Yes | Yes | No | None | Low | Recommended |
| `EligibilityApproved` | Authorized reviewer approves the case | Participant is cleared to proceed | Case ID, reviewer, decision timestamp, reason | BC-11, BC-19, BC-32 | **Strong** | Yes | Yes | No | None (never offline) | Must be attributable to exactly one authorized reviewer | Blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) |
| `EligibilityRejected` | Authorized reviewer rejects the case | Participant is not cleared; may resubmit or appeal | Case ID, reviewer, decision timestamp, reason | BC-11, BC-19, BC-32 | **Strong** | Yes | Yes | No | None | Same as above | Blocked pending OD-07 |

## BC-10 Sports Catalog

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `SportDefinitionPublished` | A sport/event definition is confirmed and versioned | Downstream contexts may now reference this definition | Sport ID, version, rule-source reference | BC-11, BC-12, BC-18 | Eventual (versioned) | Yes | No | Yes (catalog listing) | Low | Must be versioned — never mutate a version in place | Requires validation ([Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)) |

## BC-11 Competition Entries

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `CompetitionEntrySubmitted` | Coach submits an entry for an event | An entry request exists | Entry ID, athlete ref, event ref | BC-12 | Eventual | Yes | Yes | No | Low | Low | Recommended |
| `CompetitionEntryConfirmed` | Entry passes validation (eligibility, limits) | Entry is valid for tournament planning | Entry ID, confirmation timestamp | BC-12 | Strong | Yes | Yes | No | Low | Must reflect current eligibility status at confirmation time | Recommended |
| `EntryLocked` | Entry window closes / entries locked for draw | No further changes without exception process | Event ID, lock timestamp | BC-12 | **Strong** | Yes | Yes | Low | None | Must be exactly-once per event | Recommended |

## BC-12 Tournament Management *(High-Integrity — progression)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `TournamentCreated` | Tournament structure initialized for an event | A competition structure exists | Tournament ID, event ref, format | BC-14, BC-29 | Eventual | Yes | No | Low | Low | Low | Recommended |
| `DrawCompleted` | Seeding/draw process finalized | Initial competition structure is fixed | Tournament ID, draw result | BC-14, BC-29 | Strong | Yes | Yes | Yes | Low | Must not be regenerated silently once published | Recommended |
| `MatchScheduled` | A match/heat is assigned a venue/time slot | Match now has a fixed time/place | Match ID, venue ref, schedule slot | BC-13, BC-15, BC-29 | Strong | Yes | Yes | Yes | Medium (venue cache) | Rescheduling must supersede, not duplicate | Recommended |

## BC-13 Technical Officials

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `OfficialAssigned` | Tournament Manager assigns an official | Official is designated for a match/venue | Assignment ID, official ref, match/venue ref | BC-15, BC-16 | Eventual | Yes | Yes | No | Medium | Reassignment must supersede prior assignment | Recommended |

## BC-15 Scoring *(High-Integrity)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `ScoreRecorded` | Scorer/official captures a score | A raw score fact exists | Score record ID, match ref, value(s) | BC-16 (via ACL, post-validation) | Eventual (venue-local first) | Yes | No | No | **Critical** (primary offline capture point) | Must carry device/actor identity to prevent duplicate submission on reconnect | Recommended |
| `ScoreCorrected` | Authorized official corrects a previously recorded score | A prior score is superseded, not deleted | Score record ID, prior value, new value, reason, actor | BC-16 | Strong | Yes | No | No | Medium | Must preserve original value (no destructive overwrite) | Recommended |
| `ScoreValidated` | Authorized reviewer validates the score record | Score is confirmed ready for result assembly | Score record ID, validator, timestamp | BC-16 | **Strong** | Yes | No | No | None | Must be attributable to a validator distinct from the entering scorer (separation of duties) | Recommended |

## BC-16 Official Results *(High-Integrity)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `ResultGenerated` | Validated scores assembled into a draft result | A result exists pending certification | Result ID, source score refs, computed ranking | BC-17 (visibility) | Strong | Yes | No | No | None | Must be traceable to exact source score record versions | Recommended |
| `ResultCertified` | Authorized role certifies the result as official | Result is now an Official Result | Result ID, certifier, timestamp | BC-18, BC-33 | **Strong** | Yes | Yes | No | None | Must be exactly-once per result version | Blocked pending [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) |
| `ResultPublished` | Publication-authorized role releases result | Result is now a Published Result | Result ID, publisher, timestamp | BC-29, BC-31 | Eventual (near-real-time target) | Yes | Yes | **Yes** | None | Must be idempotent for public feed refresh | Recommended |

## BC-17 Protest and Appeals *(High-Integrity)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `ProtestFiled` | Authorized party files a protest against a result | A formal challenge exists | Protest ID, result ref, filer, grounds | BC-16 (hold trigger) | Strong | Yes | Yes | Low | Low | Must reference an immutable result version | Requires validation ([Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)) |
| `ResultPlacedOnHold` | Protest filing triggers a hold on the affected result | Result publication/finality is frozen | Result ID, hold reason, protest ref | BC-16, BC-29 | **Strong** | Yes | Yes | Yes (hold notice) | None | Must prevent concurrent publication | Recommended |
| `ProtestResolved` | Authorized adjudicator resolves the protest | Protest outcome determined; hold may be lifted | Protest ID, decision, resolver, timestamp | BC-16, BC-18, BC-29 | **Strong** | Yes | Yes | Yes (outcome) | None | Must be exactly-once terminal decision per protest | Requires validation (OD-09) |

## BC-18 Medal Tally and Team Standings *(High-Integrity, derived)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `MedalAwarded` | Certified result triggers medal computation | A medal is assigned for a placement | Medal ID, result ref, delegation ref, medal type | BC-29, BC-33 | **Strong** | Yes | Yes | Yes | None | Must derive solely from `ResultCertified`/`ResultPublished`, never manual entry | Blocked pending [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) |
| `MedalTallyRecalculated` | A protest resolution or correction changes an upstream result | Tally is recomputed from current certified results | Tally snapshot ID, trigger reference | BC-29, BC-33 | **Strong** | Yes | Yes | Yes | None | Must produce a new versioned snapshot, not overwrite prior public tally silently | Blocked pending OD-12 |

## BC-19 Accreditation *(High-Integrity)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `AccreditationIssued` | Accreditation Officer issues a credential | A valid credential exists for a participant | Credential ID, participant ref, category, validity period | BC-20 (cached for offline) | Eventual (must sync before use) | Yes | Yes | No | Low (issuance) | Must not double-issue for same participant/category | Requires validation ([Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage)) |
| `AccreditationRevoked` | Credential is revoked (e.g., withdrawal, misconduct) | Credential is no longer valid | Credential ID, revocation reason, timestamp | BC-20 (**must propagate to offline scanners with priority**) | **Strong for propagation urgency, though delivery itself is eventual under offline conditions** | Yes | Yes | No | Low | Revocation lists must be prioritized in sync order — this is the primary offline-security risk (RSK-08) | Recommended |

## BC-20 Access Validation *(High-Integrity, high-volume offline)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `AccessGranted` | Scan validates successfully against cached credential set | Participant granted access | Scan ID, credential ref, access point, device ID | BC-22/23/24 (context-specific) | Eventual | Yes | No | No | **Critical** | Must be idempotent on duplicate scan/reconnect | Recommended |
| `AccessDenied` | Scan fails validation | Access refused; possible security concern | Scan ID, denial reason, device ID | BC-25 | Eventual | Yes | Yes (if pattern suggests concern) | No | Critical | Must distinguish "revoked" from "not yet synced" denial reasons | Recommended |

## BC-21 Medical Operations *(Restricted)*

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `MedicalIncidentRecorded` | Medical team logs an encounter/injury | A medical incident exists on record | Incident ID, participant ref (restricted), severity | BC-09 (status flag only, via ACL) | Eventual | Yes | Yes (internal, restricted) | No | High (field capture) | Must not be exposed to non-Medical contexts beyond the ACL-filtered status flag | Requires validation ([Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling)) |

## BC-14 Venue and Schedule

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `VenueScheduleChanged` | A schedule revision occurs | Public/participant-facing schedule must update | Venue ID, changed slots, reason | BC-12, BC-29, BC-31 | Strong (must not show stale schedule) | Yes | Yes | Yes | Medium | Must supersede, not silently overwrite, the prior schedule | Recommended |

## BC-28 Media and Communications

| Event | Trigger | Business Meaning | Key Payload Concepts | Expected Consumers | Consistency | Audit | Notification | Public | Offline | Idempotency Concern | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `AnnouncementPublished` | Media Committee approves and publishes an announcement | Public/delegation-facing communication released | Announcement ID, content reference, publisher | BC-29, BC-31 | Eventual | Yes | Yes | Yes | Low | Must be idempotent for feed refresh | Recommended |

## Cross-Cutting (Generic Domains — Consumed, Not Originated Independently)

`BC-32 Audit and Compliance` and `BC-33 Reporting and Analytics` are event **consumers** across nearly every table above rather than distinct event originators; they are omitted from per-context tables to avoid duplication. `BC-31 Notifications` is triggered by nearly every event marked "Yes" in the Notification column above and is likewise not separately tabulated.
