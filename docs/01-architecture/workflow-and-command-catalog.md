# PMMS Workflow and Command Catalog

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [domain-events-catalog.md](domain-events-catalog.md) · [high-integrity-domain-rules.md](high-integrity-domain-rules.md)

This catalog documents conceptual workflows (multi-step business processes) and the commands (single intentional actions) that drive them. **No application code, controllers, jobs, or routes are defined here** — these are business-process candidates for Phase 0.3+ design.

---

## Workflows

### WF-01 — Meet Lifecycle
- **Owning context:** [BC-04 Meet Administration](bounded-context-catalog.md#bc-04--meet-administration)
- **Trigger:** Organizing Committee decides to stage a new meet.
- **Actors:** Meet Director, Meet Administrator.
- **Preconditions:** Host organization identified; platform onboarded (BC-01).
- **Main steps:** Create meet → configure dates/venues/sports list → open registration window → activate → operate → close registration → close meet → archive.
- **Decisions:** Whether readiness criteria are met before activation; whether closure conditions are met.
- **State changes:** Meet status: Draft → Configured → Active → Closed → Archived.
- **Domain events:** `MeetCreated`, `MeetActivated`, `MeetClosed`, `MeetArchived`.
- **Failure conditions:** Attempt to activate an incompletely configured meet; attempt to close a meet with unresolved protests.
- **Compensation/correction path:** Configuration may be revised before activation; post-activation changes require a documented, audited change, not silent edits.
- **Audit requirements:** Every state transition logged with actor and timestamp.
- **Offline behavior:** Not applicable — meet lifecycle transitions require connectivity.
- **External validation required:** None beyond DepEd's internal approval process.

### WF-02 — Delegation Onboarding
- **Owning context:** [BC-06 Delegation Management](bounded-context-catalog.md#bc-06--delegation-management)
- **Trigger:** A school/grouping intends to participate in an active meet.
- **Actors:** Delegation head, Secretariat.
- **Preconditions:** Meet is active and registration window is open; organization exists in BC-03.
- **Main steps:** Register delegation → confirm delegation officials/contacts → Organizing Committee confirms participation.
- **Decisions:** Whether the delegation meets participation requirements (e.g., proper authorization from the school).
- **State changes:** Delegation status: Registered → Confirmed.
- **Domain events:** `DelegationRegistered`, `DelegationConfirmed`.
- **Failure conditions:** Duplicate delegation registration for the same organization/meet.
- **Compensation/correction path:** Registration may be corrected before confirmation; post-confirmation changes are logged amendments.
- **Audit requirements:** Standard.
- **Offline behavior:** Low relevance — typically performed with connectivity ahead of the meet.
- **External validation required:** Delegation hierarchy rules ([Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy)).

### WF-03 — Athlete Registration
- **Owning context:** [BC-08 Athlete Registration](bounded-context-catalog.md#bc-08--athlete-registration)
- **Trigger:** Coach/delegation head registers an athlete.
- **Actors:** Coach, Delegation head, Secretariat.
- **Preconditions:** Delegation confirmed (WF-02); athlete resolved in Participant Registry (BC-07) or created as new.
- **Main steps:** Resolve/create participant identity → submit registration → capture guardian consent (if minor) → Secretariat review → registration completed.
- **Decisions:** Whether the participant is a new identity or matches an existing record (duplicate-detection assist, human-confirmed).
- **State changes:** Registration status: Draft → Submitted → Under Review → Completed / Withdrawn.
- **Domain events:** `ParticipantCreated` or `ParticipantMatched`, `AthleteRegistered`, `RegistrationSubmitted`, `RegistrationWithdrawn`.
- **Failure conditions:** Incomplete required data; unresolved duplicate identity.
- **Compensation/correction path:** Registration may be amended before eligibility review begins; withdrawal is always available.
- **Audit requirements:** Standard, elevated for identity-correction events.
- **Offline behavior:** Low relevance — typically performed ahead of the meet with connectivity.
- **External validation required:** Guardian consent mechanism — [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access).

### WF-04 — Eligibility Validation *(High-Integrity)*
- **Owning context:** [BC-09 Eligibility and Clearance](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity)
- **Trigger:** Registration submitted (WF-03) creates an eligibility case.
- **Actors:** Secretariat/eligibility validator (role TBD — see [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)), Schools Division Office.
- **Preconditions:** Registration submitted with required evidence.
- **Main steps:** Case created → evidence submitted (BC-30 reference) → reviewer evaluates against source-backed criteria → decision (approve/reject) → if rejected, return for correction or appeal.
- **Decisions:** Approve, reject, or return for correction; whether an appeal is warranted.
- **State changes:** Case status: Submitted → Under Review → Approved / Rejected → (Appealed → Resolved).
- **Domain events:** `EligibilityRequirementsSubmitted`, `EligibilityApproved`, `EligibilityRejected`.
- **Failure conditions:** Missing evidence; reviewer lacks defined authority (blocked until OD-07 resolved).
- **Compensation/correction path:** Rejected cases may be resubmitted with corrected evidence; approved cases may only be reversed through a documented, audited reversal — never a silent status flip.
- **Audit requirements:** **Critical** — every decision requires actor, timestamp, and reason.
- **Offline behavior:** **None** — eligibility decisions are never finalized offline (see [high-integrity-domain-rules.md](high-integrity-domain-rules.md)).
- **External validation required:** **Blocking** — eligibility criteria and approval authority, [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

### WF-05 — Accreditation Issuance *(High-Integrity)*
- **Owning context:** [BC-19 Accreditation](bounded-context-catalog.md#bc-19--accreditation-high-integrity)
- **Trigger:** Eligibility approved (WF-04) or a non-athlete role (official, staff) requires access credentials.
- **Actors:** Accreditation Officer.
- **Preconditions:** Participant identity resolved (BC-07); eligibility cleared where applicable (BC-09).
- **Main steps:** Accreditation request → category determination → credential generation (including QR token) → issuance → (later) revocation/reprint as needed.
- **Decisions:** Which accreditation category applies; whether to revoke/reissue.
- **State changes:** Credential status: Requested → Issued → Active → Revoked/Expired.
- **Domain events:** `AccreditationIssued`, `AccreditationRevoked`.
- **Failure conditions:** Request without cleared eligibility (where required); duplicate issuance for the same participant/category.
- **Compensation/correction path:** Reissue/reprint workflow for lost/damaged credentials; revocation for misuse.
- **Audit requirements:** High.
- **Offline behavior:** Issuance itself is low-offline-relevance (performed at accreditation stations, typically connected); the *validation* of issued credentials is the high-offline-relevance step (see WF-15).
- **External validation required:** Accreditation coverage scope — [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage).

### WF-06 — Competition Entry Submission
- **Owning context:** [BC-11 Competition Entries](bounded-context-catalog.md#bc-11--competition-entries)
- **Trigger:** Coach submits an athlete/team for a specific event.
- **Actors:** Coach, Tournament Manager.
- **Preconditions:** Athlete registered (WF-03) and eligibility cleared (WF-04); sport/event definition published (BC-10).
- **Main steps:** Submit entry → validate against entry limits/eligibility → confirm → (entry window closes) lock.
- **Decisions:** Whether entry limits are exceeded; whether substitution is permitted.
- **State changes:** Entry status: Submitted → Confirmed → Locked / Withdrawn.
- **Domain events:** `CompetitionEntrySubmitted`, `CompetitionEntryConfirmed`, `EntryLocked`.
- **Failure conditions:** Entry submitted for an ineligible or unregistered athlete; entry limit exceeded.
- **Compensation/correction path:** Substitution/withdrawal before lock; post-lock changes require an explicit, audited exception process.
- **Audit requirements:** Standard, elevated at lock.
- **Offline behavior:** Low relevance — pre-competition administrative step.
- **External validation required:** Athlete multi-sport participation rules (DD-11 in [domain-open-decisions.md](domain-open-decisions.md)).

### WF-07 — Tournament Setup
- **Owning context:** [BC-12 Tournament Management](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression)
- **Trigger:** Entries locked for an event (WF-06).
- **Actors:** Tournament Manager.
- **Preconditions:** Sport/event format defined (BC-10); entries locked.
- **Main steps:** Select format → seed entries → generate draw/bracket/pool → publish initial structure.
- **Decisions:** Seeding methodology (per sport rules); format selection.
- **State changes:** Tournament status: Not Started → Draw Generated → Published.
- **Domain events:** `TournamentCreated`, `DrawCompleted`.
- **Failure conditions:** Entries not locked; format undefined for the sport.
- **Compensation/correction path:** Draw regeneration before publication is permitted; post-publication changes require documented exception handling (e.g., a withdrawal after the draw).
- **Audit requirements:** High — draws are a competition-integrity artifact.
- **Offline behavior:** Low relevance for generation; the published structure is cached for venue-level offline reference.
- **External validation required:** Sport-specific format/seeding rules (DD-12, DD-13 in [domain-open-decisions.md](domain-open-decisions.md)).

### WF-08 — Officials Assignment
- **Owning context:** [BC-13 Technical Officials](bounded-context-catalog.md#bc-13--technical-officials)
- **Trigger:** Matches/heats scheduled (WF-09) require officiating coverage.
- **Actors:** Tournament Manager, Technical Delegate.
- **Preconditions:** Official qualification/certification on file (BC-07 identity + BC-13 qualification record); match/venue/time known.
- **Main steps:** Identify need → propose assignment (advisory AI conflict-check permitted) → official accepts/declines → confirm.
- **Decisions:** Conflict-of-interest check; qualification match to sport/event.
- **State changes:** Assignment status: Proposed → Accepted / Declined → Confirmed.
- **Domain events:** `OfficialAssigned`.
- **Failure conditions:** Assignment conflict (double-booking); unqualified official proposed.
- **Compensation/correction path:** Reassignment supersedes prior assignment with reason logged.
- **Audit requirements:** High.
- **Offline behavior:** Medium — assignment list cached at venue for day-of reference.
- **External validation required:** Identity/qualification ownership boundary (DD-02 in [domain-open-decisions.md](domain-open-decisions.md)).

### WF-09 — Schedule Finalization
- **Owning context:** [BC-14 Venue and Schedule](bounded-context-catalog.md#bc-14--venue-and-schedule) (Partnership with [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression))
- **Trigger:** Tournament structure exists (WF-07) and requires time/venue slots.
- **Actors:** Organizing Committee, Tournament Manager.
- **Preconditions:** Venue readiness confirmed; tournament match list available.
- **Main steps:** Identify required slots → allocate venue/time → detect and resolve conflicts → finalize → publish.
- **Decisions:** Conflict resolution when multiple matches compete for the same venue/time (advisory AI conflict-detection permitted; resolution is human).
- **State changes:** Schedule status: Draft → Conflict-Checked → Finalized → Published.
- **Domain events:** `MatchScheduled`, `VenueScheduleChanged`, `ScheduleFinalized`.
- **Failure conditions:** Unresolved venue/time conflict at finalization.
- **Compensation/correction path:** Revisions before finalization are unrestricted; post-publication revisions trigger `VenueScheduleChanged` with mandatory notification.
- **Audit requirements:** High (public-facing artifact).
- **Offline behavior:** Medium — published schedule cached for venue-level and mobile access.
- **External validation required:** Boundary between Tournament Management and Venue/Schedule ownership (DD-07 in [domain-open-decisions.md](domain-open-decisions.md)).

### WF-10 — Score Capture *(High-Integrity)*
- **Owning context:** [BC-15 Scoring](bounded-context-catalog.md#bc-15--scoring-high-integrity)
- **Trigger:** A match/heat concludes or produces measurable results.
- **Actors:** Scorer, Timer, Technical Official.
- **Preconditions:** Match scheduled and officials assigned (WF-08, WF-09).
- **Main steps:** Capture raw score(s)/time(s)/measurement(s) → record source device/actor → (if error) correct via versioned revision.
- **Decisions:** None at capture (capture is factual recording); corrections require a decision to revise.
- **State changes:** Score record status: Captured → (Corrected)* → Ready for Validation.
- **Domain events:** `ScoreRecorded`, `ScoreCorrected`.
- **Failure conditions:** Device failure mid-capture; conflicting simultaneous entries from multiple devices.
- **Compensation/correction path:** Corrections are additive/versioned, never destructive overwrites (see [high-integrity-domain-rules.md](high-integrity-domain-rules.md)).
- **Audit requirements:** **Critical**.
- **Offline behavior:** **Critical relevance** — this is a primary offline capture point; see [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md).
- **External validation required:** Sport-specific scoring model — [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source).

### WF-11 — Score Validation *(High-Integrity)*
- **Owning context:** [BC-15 Scoring](bounded-context-catalog.md#bc-15--scoring-high-integrity)
- **Trigger:** Score record captured (WF-10).
- **Actors:** Authorized validating official (distinct from the entering scorer — separation of duties).
- **Preconditions:** Score captured and, if offline, successfully synchronized.
- **Main steps:** Review captured score(s) → confirm accuracy → validate.
- **Decisions:** Accept as-is, or trigger a correction (returns to WF-10).
- **State changes:** Score record status: Ready for Validation → Validated.
- **Domain events:** `ScoreValidated`.
- **Failure conditions:** Validator is the same actor who entered the score (must be blocked by design).
- **Compensation/correction path:** Rejection returns the record for correction; never silently altered by the validator.
- **Audit requirements:** **Critical**.
- **Offline behavior:** **None** — validation is not finalized offline, even though capture (WF-10) is.
- **External validation required:** Roles authorized to validate per sport — [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).

### WF-12 — Result Certification *(High-Integrity)*
- **Owning context:** [BC-16 Official Results](bounded-context-catalog.md#bc-16--official-results-high-integrity)
- **Trigger:** Score record(s) validated (WF-11) for a match/event.
- **Actors:** Tournament Manager or Technical Delegate (role TBD).
- **Preconditions:** All required score records for the event validated.
- **Main steps:** Assemble result from validated scores (via ACL) → compute ranking/placement → certify as official.
- **Decisions:** Whether all inputs are complete and consistent before certification.
- **State changes:** Result status: Generated → Certified.
- **Domain events:** `ResultGenerated`, `ResultCertified`.
- **Failure conditions:** Incomplete score inputs; certifier lacks defined authority (blocked until OD-08 resolved).
- **Compensation/correction path:** A certified result may only be corrected through versioned supersession triggered by WF-13 (Protest Resolution) or an equivalent documented exception — never a direct edit.
- **Audit requirements:** **Critical**.
- **Offline behavior:** **None**.
- **External validation required:** **Blocking** — [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).

### WF-13 — Result Publication
- **Owning context:** [BC-16 Official Results](bounded-context-catalog.md#bc-16--official-results-high-integrity)
- **Trigger:** Result certified (WF-12) and publication window/authority conditions met.
- **Actors:** Publication-authorized role (may be distinct from certifying role).
- **Preconditions:** No active protest hold on the result.
- **Main steps:** Confirm no hold → publish → propagate to Public Information (BC-29) and notifications (BC-31).
- **Decisions:** Timing of publication relative to protest window (per sport/DepEd policy).
- **State changes:** Result status: Certified → Published.
- **Domain events:** `ResultPublished`.
- **Failure conditions:** Publication attempted while a hold is active (must be blocked).
- **Compensation/correction path:** A correction after publication follows WF-14 (Protest Resolution) and re-publishes a new result version with visible supersession, never a silent edit.
- **Audit requirements:** Critical.
- **Offline behavior:** None (publication requires connectivity to reach BC-29/BC-31).
- **External validation required:** Protest window timing — [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

### WF-14 — Protest Resolution *(High-Integrity)*
- **Owning context:** [BC-17 Protest and Appeals](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity)
- **Trigger:** A protest is filed against a certified or published result.
- **Actors:** Filer (delegation/coach), Technical Delegate (adjudicator, role TBD).
- **Preconditions:** Filing occurs within the defined deadline (source: DepEd/sport policy, not invented).
- **Main steps:** File protest → place result on hold → gather evidence → adjudicate → decide → (if applicable) appeal → resolve → lift hold and trigger result correction if upheld.
- **Decisions:** Uphold or deny the protest; whether an appeal is available and to whom.
- **State changes:** Protest status: Filed → Under Review → Decided → (Appealed → Resolved).
- **Domain events:** `ProtestFiled`, `ResultPlacedOnHold`, `ProtestResolved`.
- **Failure conditions:** Filing after deadline; adjudicator lacks defined authority (blocked until OD-09 resolved).
- **Compensation/correction path:** If upheld, triggers a versioned result correction (WF-12 supersession) and downstream medal tally recalculation (WF-15).
- **Audit requirements:** **Critical** — full evidentiary trail required.
- **Offline behavior:** Low — adjudication is not finalized offline.
- **External validation required:** **Blocking** — [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

### WF-15 — Medal Tally *(High-Integrity, derived)*
- **Owning context:** [BC-18 Medal Tally and Team Standings](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived)
- **Trigger:** Result published (WF-13) or a protest resolution changes a certified result (WF-14).
- **Actors:** Tally Team (role TBD).
- **Preconditions:** Only certified/published official results are read as input — never raw scores.
- **Main steps:** Read certified result → apply medal/team-point rules → award medal → update tally snapshot → publish.
- **Decisions:** Tie-breaking per defined rules (source: DepEd/sport policy).
- **State changes:** Tally status: Preliminary → Recalculated (on correction) → Published snapshot.
- **Domain events:** `MedalAwarded`, `MedalTallyRecalculated`.
- **Failure conditions:** Attempt to compute tally from unvalidated/uncertified results (must be structurally prevented).
- **Compensation/correction path:** Recalculation produces a new versioned snapshot; prior public snapshot is retained for historical/audit reference, not deleted.
- **Audit requirements:** **Critical**.
- **Offline behavior:** None.
- **External validation required:** **Blocking** — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), OD-13.

### WF-16 — Access Scanning *(High-Integrity, offline-critical)*
- **Owning context:** [BC-20 Access Validation](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline)
- **Trigger:** A participant presents a credential at a venue/meal/billeting/transport access point.
- **Actors:** Security/gate staff, committee scanners.
- **Preconditions:** Device has a synced (possibly slightly stale) credential-validity cache from Accreditation (BC-19).
- **Main steps:** Scan credential → validate against local cache → grant/deny access → log scan (locally if offline) → sync when connectivity resumes.
- **Decisions:** Grant vs. deny based on cached validity, including handling of ambiguous/stale-cache cases.
- **State changes:** Scan record: Captured (local) → Synced.
- **Domain events:** `AccessGranted`, `AccessDenied`.
- **Failure conditions:** Duplicate scan on reconnect; credential revoked after last sync but before device update (must fail safe per policy — see [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md)).
- **Compensation/correction path:** Denied-in-error cases are handled by a manual override workflow with mandatory reason capture, not silent re-scanning.
- **Audit requirements:** High.
- **Offline behavior:** **Critical** — this is the primary high-volume offline workflow in PMMS.
- **External validation required:** QR validation rules — [Phase 0.1 OD-28](../00-product/open-decisions.md#od-28--qr-validation-rules).

### WF-17 — Medical Incident Handling
- **Owning context:** [BC-21 Medical Operations](bounded-context-catalog.md#bc-21--medical-operations-restricted)
- **Trigger:** A medical event occurs requiring attention.
- **Actors:** Medical Team.
- **Preconditions:** None (must be usable even without prior participant record access, for emergencies).
- **Main steps:** Record incident → treat/refer → log outcome → (if relevant to eligibility) publish a minimal clearance-status flag via ACL to BC-09.
- **Decisions:** Severity classification; referral necessity.
- **State changes:** Incident status: Recorded → Treated/Referred → Closed.
- **Domain events:** `MedicalIncidentRecorded`.
- **Failure conditions:** Device unavailable during an emergency (must have a paper/offline fallback, per [Phase 0.1 constraints](../00-product/assumptions-constraints-risks.md#2-constraints)).
- **Compensation/correction path:** Incident records may be amended with a visible correction trail; never deleted.
- **Audit requirements:** **Critical** (sensitivity), restricted access.
- **Offline behavior:** High — field capture is the primary use case.
- **External validation required:** **Blocking** — [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

### WF-18 — Billeting Assignment
- **Owning context:** [BC-22 Billeting](bounded-context-catalog.md#bc-22--billeting)
- **Trigger:** Delegation confirmed (WF-02) and requires accommodation.
- **Actors:** Billeting Committee.
- **Preconditions:** Facility capacity known.
- **Main steps:** Assign delegation to facility/room → confirm → check-in at arrival → check-out at departure.
- **Decisions:** Room/facility allocation logic (capacity-based).
- **State changes:** Assignment status: Assigned → Checked-In → Checked-Out.
- **Domain events:** (see [domain-events-catalog.md](domain-events-catalog.md) — `BilletingAssigned`, `BilletingCheckedIn` conceptual candidates.)
- **Failure conditions:** Over-capacity assignment.
- **Compensation/correction path:** Reassignment with reason logged.
- **Audit requirements:** Standard.
- **Offline behavior:** Medium — check-in/out benefits from offline tolerance at accommodation sites.
- **External validation required:** Whether billeting applies to all provincial meets ([Phase 0.1 stakeholder-register.md](../00-product/stakeholder-register.md)).

### WF-19 — Food Distribution
- **Owning context:** [BC-23 Food Services](bounded-context-catalog.md#bc-23--food-services)
- **Trigger:** A scheduled meal period occurs.
- **Actors:** Food Committee.
- **Preconditions:** Meal entitlements established from delegation/athlete headcount.
- **Main steps:** Establish entitlement → validate at distribution point (via BC-20 scan) → distribute → record consumption/exceptions.
- **Decisions:** Handling of dietary exceptions.
- **State changes:** Entitlement status: Issued → Validated → Consumed.
- **Domain events:** `MealEntitlementIssued`, `MealDistributed` (conceptual candidates).
- **Failure conditions:** Entitlement mismatch (over/under count).
- **Compensation/correction path:** Manual override with reason capture for edge cases.
- **Audit requirements:** Standard.
- **Offline behavior:** Medium–High — distribution points often lack reliable connectivity.
- **External validation required:** None blocking.

### WF-20 — Transportation Dispatch
- **Owning context:** [BC-24 Transportation](bounded-context-catalog.md#bc-24--transportation)
- **Trigger:** A delegation requires transport (arrival, inter-venue, departure).
- **Actors:** Transportation Committee.
- **Preconditions:** Route/vehicle/driver available.
- **Main steps:** Assign trip → dispatch → board → arrive → record delays/incidents.
- **Decisions:** Route and vehicle assignment.
- **State changes:** Trip status: Assigned → Dispatched → Boarding → Completed.
- **Domain events:** `TransportTripDispatched`, `TransportIncidentReported`.
- **Failure conditions:** Vehicle/driver unavailability at dispatch time.
- **Compensation/correction path:** Reassignment with delay notification.
- **Audit requirements:** Standard.
- **Offline behavior:** Medium — mobile dispatch tracking benefits from offline tolerance.
- **External validation required:** None blocking.

### WF-21 — Security Incident Management
- **Owning context:** [BC-25 Security Operations](bounded-context-catalog.md#bc-25--security-operations)
- **Trigger:** A security-relevant event occurs (access denial pattern, crowd incident, lost item, credential misuse).
- **Actors:** Security Committee.
- **Preconditions:** None (must function even under connectivity loss).
- **Main steps:** Report incident → classify (advisory AI assist permitted) → respond → escalate if needed → close.
- **Decisions:** Severity and escalation path.
- **State changes:** Incident status: Reported → Responding → Escalated → Closed.
- **Domain events:** `SecurityIncidentReported`, `SecurityIncidentEscalated`.
- **Failure conditions:** Delayed reporting due to connectivity loss.
- **Compensation/correction path:** Amendments logged, not overwritten.
- **Audit requirements:** High.
- **Offline behavior:** High.
- **External validation required:** None blocking.

### WF-22 — Public Announcement Publication
- **Owning context:** [BC-28 Media and Communications](bounded-context-catalog.md#bc-28--media-and-communications) → [BC-29 Public Information](bounded-context-catalog.md#bc-29--public-information-non-authoritative)
- **Trigger:** A meet-related communication needs to reach delegations/public (schedule change, advisory, emergency notice).
- **Actors:** Media Committee.
- **Preconditions:** Content approved through the Media Committee's internal review.
- **Main steps:** Draft → approve → publish → propagate to Public Information and Notifications.
- **Decisions:** Approval and publication timing.
- **State changes:** Announcement status: Draft → Approved → Published.
- **Domain events:** `AnnouncementPublished`.
- **Failure conditions:** Unapproved content published directly (must be structurally prevented).
- **Compensation/correction path:** Corrections republish a new version with a visible correction notice.
- **Audit requirements:** Standard–High (accuracy of official communications).
- **Offline behavior:** Low.
- **External validation required:** None blocking.

### WF-23 — Meet Closure and Archiving
- **Owning context:** [BC-04 Meet Administration](bounded-context-catalog.md#bc-04--meet-administration) (coordinating), [BC-30 Document and Records](bounded-context-catalog.md#bc-30--document-and-records)
- **Trigger:** Competition complete, all protests resolved, all committees report readiness for closure.
- **Actors:** Meet Director, Organizing Committee, all Committee heads.
- **Preconditions:** No open protests (WF-14); final medal tally published (WF-15); financial/operational reconciliation complete.
- **Main steps:** Confirm closure readiness across committees → close meet → generate final reports → archive records → produce historical snapshot.
- **Decisions:** Whether all closure preconditions are genuinely met.
- **State changes:** Meet status: Active → Closed → Archived.
- **Domain events:** `MeetClosed`, `MeetArchived`.
- **Failure conditions:** Closure attempted with unresolved protests or incomplete committee reports (must be blocked).
- **Compensation/correction path:** None post-archiving beyond a formal, audited record amendment process.
- **Audit requirements:** Critical (this seals the institutional record of the meet).
- **Offline behavior:** None.
- **External validation required:** Records retention requirements — [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements).

---

## Command Candidates

Commands are the single intentional actions that drive the workflows above. Each is a conceptual candidate only — no method signatures, classes, or routes are implied.

| Command | Owning Context | Expected Actor | Preconditions | Outcome | Possible Event | Audit Requirement | Validation Source Required |
|---|---|---|---|---|---|---|---|
| `CreateMeet` | BC-04 | Meet Administrator | Host organization confirmed | Meet exists in Draft status | `MeetCreated` | Standard | None |
| `ActivateMeet` | BC-04 | Meet Director | Meet fully configured | Meet is operational | `MeetActivated` | Standard | None |
| `RegisterDelegation` | BC-06 | Delegation head/Secretariat | Meet active, org exists | Delegation registered | `DelegationRegistered` | Standard | Delegation hierarchy (DD-04 ref) |
| `RegisterAthlete` | BC-08 | Coach/Secretariat | Delegation confirmed | Athlete registration exists | `AthleteRegistered` | Standard | None |
| `SubmitEligibilityCase` | BC-09 | Coach/Secretariat | Registration submitted | Case ready for review | `EligibilityRequirementsSubmitted` | Standard | None |
| `ApproveEligibility` | BC-09 | Authorized validator (TBD) | Case under review, evidence complete | Participant cleared | `EligibilityApproved` | **Critical** | **[Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)** |
| `RejectEligibility` | BC-09 | Authorized validator (TBD) | Case under review | Participant not cleared | `EligibilityRejected` | **Critical** | OD-07 |
| `IssueAccreditation` | BC-19 | Accreditation Officer | Identity resolved, eligibility cleared (where required) | Credential issued | `AccreditationIssued` | High | [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage) |
| `RevokeCredential` | BC-19 | Accreditation Officer | Credential exists | Credential invalid | `AccreditationRevoked` | High | None |
| `SubmitCompetitionEntry` | BC-11 | Coach | Athlete eligible | Entry exists | `CompetitionEntrySubmitted` | Standard | None |
| `LockEntries` | BC-11 | Tournament Manager | Entry window closed | Entries immutable pending exception process | `EntryLocked` | High | None |
| `GenerateDraw` | BC-12 | Tournament Manager | Entries locked, format defined | Tournament structure exists | `DrawCompleted` | High | Sports rule source (DD-12, DD-13) |
| `AssignOfficial` | BC-13 | Tournament Manager | Official qualified, no conflict | Assignment confirmed | `OfficialAssigned` | High | None |
| `RecordScore` | BC-15 | Scorer/Technical Official | Match in progress/concluded | Score record captured | `ScoreRecorded` | **Critical** | Sports scoring model (OD-10 ref) |
| `ValidateScore` | BC-15 | Authorized validator (≠ entering scorer) | Score captured | Score validated | `ScoreValidated` | **Critical** | [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) |
| `CertifyResult` | BC-16 | Authorized certifier (TBD) | All scores validated | Result is official | `ResultCertified` | **Critical** | **OD-08 (blocking)** |
| `PublishResult` | BC-16 | Publication-authorized role | Result certified, no active hold | Result public/delegation-visible | `ResultPublished` | Critical | [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) (window timing) |
| `FileProtest` | BC-17 | Delegation/coach (authorized filer) | Within filing deadline | Protest case exists, result held | `ProtestFiled`, `ResultPlacedOnHold` | Critical | **OD-09 (blocking)** |
| `ResolveProtest` | BC-17 | Authorized adjudicator (TBD) | Evidence reviewed | Protest decided | `ProtestResolved` | Critical | **OD-09 (blocking)** |
| `RecalculateMedalTally` | BC-18 | Tally Team | Certified result available/changed | Tally snapshot updated | `MedalTallyRecalculated` | Critical | **[Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) (blocking)** |
| `ValidateAccess` | BC-20 | Scanner (device-executed on behalf of staff) | Cached credential set available | Access granted/denied | `AccessGranted` / `AccessDenied` | High | [Phase 0.1 OD-28](../00-product/open-decisions.md#od-28--qr-validation-rules) |
| `CloseMeet` | BC-04 | Meet Director | No open protests, tally published, committees report ready | Meet closed | `MeetClosed` | Critical | [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) |

Commands marked **blocking** cannot be meaningfully finalized in Phase 0.3 role/permission design until the referenced Phase 0.1 open decision is resolved — the *command* and its *owning context* are settled now; *who* is authorized to issue it is not.
