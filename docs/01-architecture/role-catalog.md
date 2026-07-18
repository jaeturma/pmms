# PMMS Role Catalog

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [permission-catalog.md](permission-catalog.md) · [assignment-model.md](assignment-model.md) · [separation-of-duties-matrix.md](separation-of-duties-matrix.md)

Roles describe **reusable responsibility categories**, not organization IDs, meet IDs, or individuals. A role grants no authority by itself — authority requires Role + Permission + Scope + (where applicable) a valid Assignment (see the Authority formula in [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md)). No role is a Laravel/Spatie seed record; this is an architectural catalog.

**Consolidation approach:** Per working rules 13–14 ("do not hard-code committee names as permanent authorization roles," "do not treat a role and an assignment as the same concept"), several of the prompt's candidate roles are deliberately **consolidated** rather than each becoming a distinct role — most notably, sport-specific officiating functions (Referee, Judge, Umpire, Scorer, Timer) are modeled as **one Technical Official role activated by assignment metadata** describing the specific function, rather than five separate roles. This is called out explicitly per entry below where consolidation occurred.

**Status legend:** `Recommended` — sound direction, low validation risk. `Requires validation` — role exists but its exact boundary/necessity needs confirmation. `Optional` — plausible but not clearly load-bearing; include only if a real need emerges. `Deferred` — reasonable future addition, not needed for initial scope.

---

## Platform Roles

### ROLE-01 — Platform Super Administrator
- **Category:** Platform
- **Purpose:** Ultimate platform configuration authority; exists to bootstrap and recover the platform itself.
- **Typical users:** A very small number of senior ICT/platform staff.
- **Default permission intent:** Platform configuration (`platform.*`), organization onboarding — **not** routine business data access.
- **Required assignment:** No — this is a rare, directly-granted role, not activated through a meet/committee assignment.
- **Allowed scopes:** Platform.
- **Sensitive capabilities:** Can create/modify Organization Administrators; can access security configuration.
- **Prohibited assumptions:** Does **not** automatically grant access to eligibility, medical, financial, or scoring data (see [phase-0.3, Section 23](phase-0.3-access-and-assignment-architecture.md#23-organization-and-meet-administration-boundaries)).
- **Separation-of-duties concerns:** Must be distinct from any business-approval role (SOD-07 in [separation-of-duties-matrix.md](separation-of-duties-matrix.md)).
- **Approval authority:** Emergency/Override tier (see Section 19 of the main document).
- **Review requirement:** Highest — reviewed at every access-review cycle without exception.
- **Validation status:** Recommended — must remain rare per Role Design Rules.

### ROLE-02 — Platform Administrator
- **Category:** Platform
- **Purpose:** Day-to-day platform configuration (reference data, feature availability) short of the Super Administrator's full authority.
- **Typical users:** Platform/ICT staff.
- **Default permission intent:** `platform.configure`, `reference-data.manage`.
- **Required assignment:** No.
- **Allowed scopes:** Platform.
- **Sensitive capabilities:** Reference-data changes affect all downstream contexts (see [domain-open-decisions.md, DD-22](domain-open-decisions.md#dd-22--shared-reference-data-ownership)).
- **Prohibited assumptions:** No business-data access.
- **Separation-of-duties concerns:** None beyond ROLE-01's.
- **Approval authority:** Operational.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-03 — Security Administrator
- **Category:** Platform
- **Purpose:** Manage security configuration, review security events, approve break-glass/impersonation requests.
- **Typical users:** Security/ICT lead.
- **Default permission intent:** `security-event.review`, `impersonation-session.approve`, `emergency-access.approve`.
- **Required assignment:** No.
- **Allowed scopes:** Platform.
- **Sensitive capabilities:** Approves emergency and impersonation access (Sections 31–32 of the main document).
- **Prohibited assumptions:** Must **not** also hold sole audit-review authority (SOD-08).
- **Separation-of-duties concerns:** Distinct from Audit Viewer (ROLE-05) and from any business approval role.
- **Approval authority:** Override/Emergency.
- **Review requirement:** Highest.
- **Validation status:** Recommended.

### ROLE-04 — Support Administrator
- **Category:** Platform
- **Purpose:** Provide user support, including (only where explicitly authorized) impersonation sessions.
- **Typical users:** Platform support staff.
- **Default permission intent:** `user-account.support-view`, `impersonation-session.request`.
- **Required assignment:** No.
- **Allowed scopes:** Platform (with per-session scope restriction — see Section 32 of the main document).
- **Sensitive capabilities:** Impersonation request — disabled by default, requires ROLE-03 approval.
- **Prohibited assumptions:** Cannot approve or certify business transactions while impersonating (see impersonation policy).
- **Separation-of-duties concerns:** SOD-08.
- **Approval authority:** Operational (elevated only during an approved, time-limited session).
- **Review requirement:** High, plus per-session review.
- **Validation status:** Requires validation — impersonation necessity itself is an open decision (AD-09 in [access-open-decisions.md](access-open-decisions.md)).

### ROLE-05 — Audit Viewer
- **Category:** Platform
- **Purpose:** Independent review of audit history without business-approval authority.
- **Typical users:** Auditors (internal or DepEd-designated).
- **Default permission intent:** `audit-event.view`, `audit-event.export`.
- **Required assignment:** No (standing platform role) or Organization-scoped, per validation.
- **Allowed scopes:** Platform or Organization.
- **Sensitive capabilities:** Export of audit records (Highly Restricted classification).
- **Prohibited assumptions:** No write access to any business record.
- **Separation-of-duties concerns:** SOD-08 — must not also be a Security or Platform Administrator.
- **Approval authority:** Review tier only.
- **Review requirement:** High.
- **Validation status:** Recommended.

---

## Organization Roles

### ROLE-06 — Organization Administrator
- **Category:** Organization
- **Purpose:** Administer PMMS for one onboarded organization (initially DepEd itself).
- **Typical users:** DepEd central ICT/administrative staff.
- **Default permission intent:** `organization.configure`, `organization-hierarchy.manage`.
- **Required assignment:** Organizational assignment, scoped to the organization.
- **Allowed scopes:** Organization.
- **Sensitive capabilities:** Configures the organizational hierarchy consumed by Delegation Management.
- **Prohibited assumptions:** **Does not** automatically grant Meet Administrator authority (Section 23 rule).
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Operational–Approval.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-07 — Regional Administrator / ROLE-08 — Division Administrator / ROLE-09 — School Administrator
- **Category:** Organization
- **Purpose:** Administer PMMS data at the Region/Division/School node of the organizational hierarchy.
- **Typical users:** Regional/Division/School ICT or administrative staff.
- **Default permission intent:** `organization-node.manage`, scoped to the specific node.
- **Required assignment:** Organizational assignment scoped to the specific Region/Division/School.
- **Allowed scopes:** Region / Division / School respectively (with strict non-inheritance across sibling nodes — see [scope-model.md](scope-model.md)).
- **Sensitive capabilities:** None beyond node-level configuration.
- **Prohibited assumptions:** A Division Administrator does not automatically see other Divisions' data.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Operational.
- **Review requirement:** Medium.
- **Validation status:** **Deferred** — these node-level admin roles depend on the Organization Directory data-source decision ([Phase 0.1 OD-06](../00-product/open-decisions.md#od-06--school-data-source)) and are not needed if BC-03 is locally maintained by a small central team at MVP.

---

## Meet Governance Roles

### ROLE-10 — Meet Director
- **Category:** Meet Governance
- **Purpose:** Ultimate authority for one meet's lifecycle (create, activate, close).
- **Typical users:** The senior organizer of a specific meet.
- **Default permission intent:** `meet.create`, `meet.activate`, `meet.close`, `meet.archive`.
- **Required assignment:** Meet assignment (Meet Director for Meet X).
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** Meet closure requires no open protests / published tally (per [BC-04 invariants](phase-0.2-domain-architecture.md#6-detailed-bounded-context-definitions)).
- **Prohibited assumptions:** Does not automatically grant scoring, eligibility, or medical access (Section 23 rule).
- **Separation-of-duties concerns:** None named directly, though closure authority intersects with SOD entries for open workflows.
- **Approval authority:** Approval tier for meet lifecycle actions.
- **Review requirement:** High, at assignment end and meet closure.
- **Validation status:** Recommended.

### ROLE-11 — Meet Administrator
- **Category:** Meet Governance
- **Purpose:** Day-to-day meet configuration under the Meet Director's authority.
- **Typical users:** Deputy organizer.
- **Default permission intent:** `meet.update`, `meet.suspend`, `meet.reopen`.
- **Required assignment:** Meet assignment.
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** Meet suspension affects all committees.
- **Prohibited assumptions:** Same as ROLE-10.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Operational–Approval.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-12 — Executive Committee Member
- **Category:** Meet Governance
- **Purpose:** Represents senior oversight of a meet without operational configuration authority.
- **Typical users:** Senior DepEd/organizing-body representatives.
- **Default permission intent:** Broad read access to meet-wide dashboards; approval authority for select high-level decisions (e.g., budget sign-off, per validation).
- **Required assignment:** Meet assignment.
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** None beyond read access unless specifically granted.
- **Prohibited assumptions:** Not an operational role by default.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Approval tier (specific actions TBD).
- **Review requirement:** Medium.
- **Validation status:** Requires validation — specific authority is undefined pending DepEd governance confirmation.

### ROLE-13 — Secretariat Head / ROLE-14 — Secretariat Staff
- **Category:** Meet Governance
- **Purpose:** Head/staff of the central administrative committee — registration, documentation, coordination hub.
- **Typical users:** Secretariat committee members.
- **Default permission intent:** Head: `athlete-registration.review`, `committee.assign-member`. Staff: `athlete-registration.create`, `document.upload`.
- **Required assignment:** Committee assignment (Secretariat, Meet X).
- **Allowed scopes:** Meet + Committee (Secretariat).
- **Sensitive capabilities:** Registration review touches Confidential-classified contact/guardian data.
- **Prohibited assumptions:** Secretariat Staff does not automatically gain Eligibility Approver authority (SOD-01).
- **Separation-of-duties concerns:** SOD-01.
- **Approval authority:** Head: Review. Staff: Operational.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-15 — Meet Auditor
- **Category:** Meet Governance
- **Purpose:** Meet-scoped independent review, narrower than the platform-wide Audit Viewer.
- **Typical users:** DepEd-designated meet auditor.
- **Default permission intent:** `audit-event.view` (meet-scoped).
- **Required assignment:** Meet assignment.
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** None beyond read access.
- **Prohibited assumptions:** No write access.
- **Separation-of-duties concerns:** SOD-08 pattern applies at meet scope too.
- **Approval authority:** Review tier.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-16 — Meet Observer
- **Category:** Meet Governance
- **Purpose:** Read-only visibility into meet operations for oversight without audit authority (e.g., a DepEd regional representative observing).
- **Typical users:** DepEd oversight staff.
- **Default permission intent:** Read-only dashboards.
- **Required assignment:** Meet assignment.
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** None.
- **Prohibited assumptions:** No write access whatsoever.
- **Separation-of-duties concerns:** None.
- **Approval authority:** Self-service (view only).
- **Review requirement:** Low.
- **Validation status:** Optional.

---

## Registration and Eligibility Roles

### ROLE-17 — Delegation Registrar
*(Consolidates the candidate "Athlete Registration Encoder" — the same responsibility, one role.)*
- **Category:** Registration
- **Purpose:** Create and submit athlete/coach registrations on behalf of a delegation.
- **Typical users:** Secretariat staff or delegation-facing encoders.
- **Default permission intent:** `athlete-registration.create`, `athlete-registration.submit`.
- **Required assignment:** Committee assignment (Secretariat) or Delegation assignment, per validation.
- **Allowed scopes:** Meet + (Committee or Delegation).
- **Sensitive capabilities:** Handles Confidential guardian/contact data.
- **Prohibited assumptions:** Cannot review/approve their own submissions (SOD-01).
- **Separation-of-duties concerns:** SOD-01.
- **Approval authority:** Self-service/Operational.
- **Review requirement:** Medium.
- **Validation status:** Recommended.

### ROLE-18 — Registration Reviewer
- **Category:** Registration
- **Purpose:** Review submitted registrations for completeness before eligibility review begins.
- **Typical users:** Secretariat staff.
- **Default permission intent:** `athlete-registration.review`, `athlete-registration.return`.
- **Required assignment:** Committee assignment (Secretariat).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** None beyond ROLE-17's.
- **Prohibited assumptions:** Reviewing completeness is **not** the same as approving eligibility (distinct from ROLE-20/21).
- **Separation-of-duties concerns:** None directly, but feeds SOD-01.
- **Approval authority:** Review.
- **Review requirement:** Medium.
- **Validation status:** Recommended, though may be consolidated with ROLE-17 pending real workflow validation.

### ROLE-19 — Eligibility Reviewer
- **Category:** Eligibility
- **Purpose:** Evaluate submitted eligibility evidence and record findings — **without final approval authority**.
- **Typical users:** Secretariat/committee staff designated for eligibility review.
- **Default permission intent:** `eligibility-case.review`, `eligibility-case.record-finding`, `eligibility-case.view-restricted-evidence`.
- **Required assignment:** Committee assignment (Eligibility), scoped to specific delegations per [Example 2 in the main document](phase-0.3-access-and-assignment-architecture.md#example-2).
- **Allowed scopes:** Meet + assigned Delegations.
- **Sensitive capabilities:** Views Restricted-classified eligibility evidence.
- **Prohibited assumptions:** **Cannot** approve/reject the case they reviewed (SOD-01) — final decision requires ROLE-20.
- **Separation-of-duties concerns:** SOD-01.
- **Approval authority:** Review.
- **Review requirement:** High.
- **Validation status:** Recommended — final authority blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

### ROLE-20 — Eligibility Approver
- **Category:** Eligibility
- **Purpose:** Render the final approve/reject decision on an eligibility case.
- **Typical users:** A designated authority distinct from ROLE-19 (specific DepEd role **blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)**).
- **Default permission intent:** `eligibility-case.approve`, `eligibility-case.reject`, `eligibility-case.reopen`.
- **Required assignment:** Committee assignment (Eligibility) or Organization-level designation, pending OD-07.
- **Allowed scopes:** Meet + assigned Delegations.
- **Sensitive capabilities:** Highest in the Eligibility domain — see [high-integrity-access-controls.md](high-integrity-access-controls.md#eligibility).
- **Prohibited assumptions:** Must not be the same individual as the case's ROLE-19 reviewer for that specific case (SOD-01, enforced at the case level, not just role level).
- **Separation-of-duties concerns:** SOD-01 — **critical**.
- **Approval authority:** Approval/Certification tier.
- **Review requirement:** Highest.
- **Validation status:** **Blocking** — [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

### ROLE-21 — Medical Clearance Reviewer
- **Category:** Eligibility (cross-referencing Medical)
- **Purpose:** Review the minimal medical clearance-status flag exposed to Eligibility via the BC-21→BC-09 Anti-Corruption Layer.
- **Typical users:** A Secretariat/Eligibility-side role — **not** a Medical Team member (who holds ROLE-27/28 instead and never appears on the Eligibility side of the ACL boundary).
- **Default permission intent:** `eligibility-case.view-medical-status` (status flag only, never raw medical data).
- **Required assignment:** Committee assignment (Eligibility).
- **Allowed scopes:** Meet + assigned Delegations.
- **Sensitive capabilities:** Must never gain access to raw Medical Operations records — enforced by the ACL, not by this role's discipline alone.
- **Prohibited assumptions:** Is not a Medical role.
- **Separation-of-duties concerns:** None directly.
- **Approval authority:** Review.
- **Review requirement:** High.
- **Validation status:** Requires validation — depends on [domain-open-decisions.md, DD-08](domain-open-decisions.md#dd-08--medical-clearance-relationship-to-eligibility).

### ROLE-22 — Document Verifier
- **Category:** Eligibility
- **Purpose:** Verify authenticity/completeness of uploaded supporting documents, as a distinct step from evidentiary review.
- **Typical users:** Secretariat staff.
- **Default permission intent:** `document.verify`.
- **Required assignment:** Committee assignment (Secretariat or Eligibility).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** Views Restricted documents.
- **Prohibited assumptions:** Verifying a document is not the same as approving the case it supports.
- **Separation-of-duties concerns:** Feeds SOD-01.
- **Approval authority:** Operational.
- **Review requirement:** Medium.
- **Validation status:** Optional — may be consolidated into ROLE-19 pending real workflow validation; kept separate here because document authenticity checking may be a distinct skill/step from eligibility-criteria evaluation.

---

## Competition Roles

### ROLE-23 — Sports Coordinator
- **Category:** Competition
- **Purpose:** Cross-sport coordination within a meet (distinct from any single sport's Tournament Manager).
- **Typical users:** A senior competition-operations staff member.
- **Default permission intent:** `sport-definition.propose`, meet-wide competition dashboard access.
- **Required assignment:** Meet assignment.
- **Allowed scopes:** Meet (all sports, read-heavy; write limited to coordination, not per-sport execution).
- **Sensitive capabilities:** None beyond visibility.
- **Prohibited assumptions:** Does not automatically hold Tournament Manager authority for any specific sport.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Operational.
- **Review requirement:** Medium.
- **Validation status:** Recommended.

### ROLE-24 — Tournament Manager / ROLE-25 — Assistant Tournament Manager
- **Category:** Competition
- **Purpose:** Own competition structure, scheduling, and officiating coordination for one or more sports within a meet.
- **Typical users:** Sport-specific competition managers.
- **Default permission intent:** `tournament.generate-draw`, `competition-entry.lock`, `official-assignment.create`, `match.schedule`.
- **Required assignment:** Sport assignment ([Example 1](phase-0.3-access-and-assignment-architecture.md#example-1)).
- **Allowed scopes:** Meet + Sport.
- **Sensitive capabilities:** Draw generation is a competition-integrity action.
- **Prohibited assumptions:** **Does not automatically hold Result Certifier authority** (Section 6 working rule explicitly named this anti-pattern) — certification requires ROLE-31/32 separately, even if the same individual sometimes holds both roles through separate assignments.
- **Separation-of-duties concerns:** SOD-03 if combined with Result Certifier without review.
- **Approval authority:** Approval tier for scheduling/draws.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-26 — Technical Delegate
- **Category:** Competition
- **Purpose:** Senior officiating authority for a sport, including protest adjudication oversight.
- **Typical users:** Senior sport-specific technical authority (title/authority pending [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)).
- **Default permission intent:** `official-assignment.approve`, `protest.review`, `protest.resolve` (pending OD-09).
- **Required assignment:** Sport assignment.
- **Allowed scopes:** Meet + Sport.
- **Sensitive capabilities:** Protest resolution is high-integrity (see [high-integrity-access-controls.md](high-integrity-access-controls.md#protest-and-appeals)).
- **Prohibited assumptions:** Must not resolve a protest connected to a result they personally certified (SOD-03).
- **Separation-of-duties concerns:** SOD-03.
- **Approval authority:** Approval/Certification tier.
- **Review requirement:** Highest.
- **Validation status:** **Blocking** — [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

### ROLE-27 — Technical Official
*(Consolidates the candidate roles Referee, Judge, Umpire, Scorer, Timer, Encoder into one role, activated with a function-specific Assignment. See Role Design Rules — "technical designations may require assignment metadata" rather than a role explosion. If real-world practice later shows these functions carry genuinely distinct permission sets, they can be split; Phase 0.3 does not assume that split is necessary yet.)*
- **Category:** Technical Officiating
- **Purpose:** Perform an assigned officiating function (scoring, timing, judging, refereeing) for specific matches/heats.
- **Typical users:** Qualified sport officials.
- **Default permission intent:** `score-record.submit` (function: Scorer/Timer/Encoder), officiating-specific actions per function (function: Referee/Judge/Umpire) — the specific permission subset activated depends on the assignment's function metadata.
- **Required assignment:** Event assignment or Venue assignment with function metadata ([Example 4](phase-0.3-access-and-assignment-architecture.md#example-4) pattern).
- **Allowed scopes:** Meet + Sport + Event/Match (assignment-scoped, not sport-wide).
- **Sensitive capabilities:** Score capture is high-integrity (see [high-integrity-access-controls.md](high-integrity-access-controls.md#scoring)).
- **Prohibited assumptions:** **A Technical Official assigned to enter a score is not automatically the validator of that same score** (SOD-02) — validation requires a separate, distinct assignment.
- **Separation-of-duties concerns:** SOD-02.
- **Approval authority:** Operational (Scorer/Timer/Encoder function) or Review (Referee/Judge/Umpire function, per sport rules).
- **Review requirement:** High.
- **Validation status:** Recommended, with the consolidation decision flagged for validation once real per-sport officiating structures are confirmed ([Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)).

### ROLE-28 — Result Validator
- **Category:** Results
- **Purpose:** Confirm a captured score record's accuracy before it can feed result certification — distinct from the entering official.
- **Typical users:** A senior Technical Official or Technical Delegate designee.
- **Default permission intent:** `score-record.validate`.
- **Required assignment:** Event assignment, distinct from the entering Technical Official's assignment for the same score record.
- **Allowed scopes:** Meet + Sport + Event/Match.
- **Sensitive capabilities:** Directly enforces SOD-02.
- **Prohibited assumptions:** Cannot validate a score they themselves entered (system-enforced, not just policy).
- **Separation-of-duties concerns:** SOD-02.
- **Approval authority:** Review.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-29 — Result Certifier
- **Category:** Results
- **Purpose:** Certify a result as official once all score inputs are validated.
- **Typical users:** Tournament Manager or Technical Delegate, per [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) (role identity blocked pending that decision).
- **Default permission intent:** `official-result.certify`, `official-result.supersede`.
- **Required assignment:** Sport or Event assignment ([Example 4](phase-0.3-access-and-assignment-architecture.md#example-4)).
- **Allowed scopes:** Meet + Sport + Event.
- **Sensitive capabilities:** Highest in the Official Results domain.
- **Prohibited assumptions:** Certification authority is **not** automatically held by the Tournament Manager role (explicit anti-pattern named in working rules).
- **Separation-of-duties concerns:** SOD-02, SOD-03.
- **Approval authority:** Certification tier.
- **Review requirement:** Highest.
- **Validation status:** **Blocking** — [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).

---

## Tally and Publication Roles

### ROLE-30 — Tally Encoder / ROLE-31 — Tally Reviewer / ROLE-32 — Tally Certifier
- **Category:** Tally
- **Purpose:** Three-stage separation for medal tally: computation trigger/encoding, review, and final certification.
- **Typical users:** Tally Team members (title TBD pending [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules)).
- **Default permission intent:** Encoder: `medal-tally.recalculate`. Reviewer: `medal-tally.review`. Certifier: `medal-tally.certify`.
- **Required assignment:** Committee assignment (Tally).
- **Allowed scopes:** Meet.
- **Sensitive capabilities:** Certifier action is the platform's most publicly visible high-integrity output.
- **Prohibited assumptions:** The same individual should not hold Encoder and Certifier for the same tally snapshot (SOD-04).
- **Separation-of-duties concerns:** SOD-04.
- **Approval authority:** Encoder: Operational. Reviewer: Review. Certifier: Certification.
- **Review requirement:** Highest for Certifier.
- **Validation status:** **Blocking** for Certifier authority specifics — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules); Encoder/Reviewer recommended.

### ROLE-33 — Result Publisher
- **Category:** Publication
- **Purpose:** Release certified results/tally to delegations and the public, as a distinct step from certification (per [domain-open-decisions.md, DD-17](domain-open-decisions.md#dd-17--public-publication-approval-chain)).
- **Typical users:** Tournament Manager or a dedicated publication role.
- **Default permission intent:** `official-result.publish`, `medal-tally.publish`.
- **Required assignment:** Sport or Meet assignment.
- **Allowed scopes:** Meet + Sport.
- **Sensitive capabilities:** Publication requires no active protest hold on the result.
- **Prohibited assumptions:** Publication authority is separate from certification authority per DD-17's recommended direction.
- **Separation-of-duties concerns:** SOD-03 pattern.
- **Approval authority:** Publication tier.
- **Review requirement:** High.
- **Validation status:** Recommended, pending DD-17/OD-09 confirmation.

### ROLE-34 — Public Information Publisher
- **Category:** Publication
- **Purpose:** Approve and publish schedules, announcements, and advisories to the Public Information surface (BC-29).
- **Typical users:** Media Committee lead.
- **Default permission intent:** `announcement.publish`, `schedule.publish`.
- **Required assignment:** Committee assignment (Media).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** Public-facing accuracy.
- **Prohibited assumptions:** Cannot publish an Official Result directly (that is ROLE-33's domain) — only schedules/announcements.
- **Separation-of-duties concerns:** None named beyond draft/approve separation within the Media committee's own workflow.
- **Approval authority:** Publication tier (Media scope only).
- **Review requirement:** Medium.
- **Validation status:** Recommended.

### ROLE-35 — Media Content Manager
- **Category:** Publication
- **Purpose:** Draft/manage media assets (photos, video, press content) without independent publication authority.
- **Typical users:** Media Committee staff.
- **Default permission intent:** `media-asset.create`, `media-asset.edit`.
- **Required assignment:** Committee assignment (Media).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** None beyond media-asset handling.
- **Prohibited assumptions:** Cannot publish without ROLE-34.
- **Separation-of-duties concerns:** Draft/approve separation.
- **Approval authority:** Operational.
- **Review requirement:** Low–Medium.
- **Validation status:** Optional.

---

## Operational Committee Roles

### ROLE-36 — Committee Head / ROLE-37 — Committee Staff
*(Generic pattern applied per committee — Medical, Billeting, Food, Transportation, Security, Finance, ICT, per [phase-0.3, Section 25](phase-0.3-access-and-assignment-architecture.md#25-committee-authorization).)*
- **Category:** Committee (generic pattern; committee-specific specializations below)
- **Purpose:** Lead/staff a specific operational committee.
- **Typical users:** Committee members.
- **Default permission intent:** Head: `committee-task.approve`, `committee-report.submit`. Staff: `committee-task.record`.
- **Required assignment:** Committee assignment, specifying which committee.
- **Allowed scopes:** Meet + specific Committee (never automatically extends to other committees — explicit Scope Evaluation Rule).
- **Sensitive capabilities:** Varies by committee — see committee-specific roles below for elevated cases (Medical, Finance, Accreditation).
- **Prohibited assumptions:** Committee membership alone does **not** grant full committee access (working rule 16) — the specific position (Head vs. Staff vs. Viewer) determines the permission subset.
- **Separation-of-duties concerns:** Committee-specific (see [separation-of-duties-matrix.md](separation-of-duties-matrix.md)).
- **Approval authority:** Head: Review–Approval. Staff: Operational.
- **Review requirement:** Medium, elevated for Medical/Finance/Security.
- **Validation status:** Recommended (generic pattern); committee-specific specializations below carry their own status.

### ROLE-38 — Medical Officer / ROLE-39 — Medical Staff
- **Category:** Committee (Medical — elevated sensitivity)
- **Purpose:** Lead/staff the Medical committee with access to restricted medical records.
- **Typical users:** Licensed medical personnel.
- **Default permission intent:** Officer: `medical-encounter.view-sensitive`, `fitness-status.issue`. Staff: `medical-encounter.record`.
- **Required assignment:** Committee assignment (Medical) + Venue/Shift assignment ([Example 3](phase-0.3-access-and-assignment-architecture.md#example-3)).
- **Allowed scopes:** Meet + Venue + Shift.
- **Sensitive capabilities:** Highest-sensitivity data category in the platform (see [high-integrity-access-controls.md](high-integrity-access-controls.md#medical)).
- **Prohibited assumptions:** Medical Staff without the Officer designation cannot issue a fitness status.
- **Separation-of-duties concerns:** SOD-09 (Medical records and public-information publishing must never be the same individual).
- **Approval authority:** Officer: Approval (fitness status). Staff: Operational.
- **Review requirement:** Highest.
- **Validation status:** Requires validation — blocked in part on [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

### ROLE-40 — Billeting Coordinator / ROLE-41 — Food Coordinator / ROLE-42 — Transportation Coordinator
- **Category:** Committee (Logistics)
- **Purpose:** Lead the respective logistics committee.
- **Typical users:** Committee heads.
- **Default permission intent:** `billeting-assignment.manage` / `meal-entitlement.manage` / `transport-trip.manage`.
- **Required assignment:** Committee assignment.
- **Allowed scopes:** Meet + Committee (+ Venue where applicable).
- **Sensitive capabilities:** Standard.
- **Prohibited assumptions:** None named.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Operational–Review.
- **Review requirement:** Medium.
- **Validation status:** Recommended.

### ROLE-43 — Security Coordinator
- **Category:** Committee (Security)
- **Purpose:** Lead security operations, including access-denial pattern review and incident escalation.
- **Typical users:** Security Committee head.
- **Default permission intent:** `security-incident.manage`, `access-scan.review`.
- **Required assignment:** Committee assignment (Security).
- **Allowed scopes:** Meet + Committee (+ Venue).
- **Sensitive capabilities:** Access-log review (Restricted classification).
- **Prohibited assumptions:** Security review is distinct from Access Control Operator's front-line scanning role (ROLE-49).
- **Separation-of-duties concerns:** SOD-10.
- **Approval authority:** Review–Approval.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-44 — ICT Coordinator
- **Category:** Committee (ICT)
- **Purpose:** Lead on-site ICT support, device readiness, connectivity.
- **Typical users:** ICT Committee head.
- **Default permission intent:** `support-ticket.manage`, `device-identity.register` (see [device-and-service-identity-model.md](device-and-service-identity-model.md)).
- **Required assignment:** Committee assignment (ICT).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** Device registration/revocation authority.
- **Prohibited assumptions:** Does not automatically hold Security Administrator authority.
- **Separation-of-duties concerns:** SOD-10 (device administration vs. security review).
- **Approval authority:** Operational–Review.
- **Review requirement:** Medium–High.
- **Validation status:** Recommended.

### ROLE-45 — Finance Coordinator
- **Category:** Committee (Finance)
- **Purpose:** Lead financial monitoring for the meet.
- **Typical users:** Finance Committee head.
- **Default permission intent:** `budget-allocation.approve`, `expense.review`.
- **Required assignment:** Committee assignment (Finance).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** Restricted-classified supporting documents.
- **Prohibited assumptions:** Cannot both encode and approve the same expense (SOD-06).
- **Separation-of-duties concerns:** SOD-06.
- **Approval authority:** Approval.
- **Review requirement:** High.
- **Validation status:** Recommended.

### ROLE-46 — Media Coordinator
- **Category:** Committee (Media)
- **Purpose:** Lead media/communications committee (distinct from the publication-focused ROLE-34/35, which may or may not be the same person — kept as separate roles to preserve draft/approve separation even when combined by assignment).
- **Typical users:** Media Committee head.
- **Default permission intent:** `committee-task.approve` (Media-scoped).
- **Required assignment:** Committee assignment (Media).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** None beyond standard committee.
- **Prohibited assumptions:** None named.
- **Separation-of-duties concerns:** SOD-09 if combined with a Medical role.
- **Approval authority:** Review–Approval.
- **Review requirement:** Medium.
- **Validation status:** Recommended.

### ROLE-47 — Accreditation Officer
- **Category:** Committee (Accreditation)
- **Purpose:** Issue, revoke, and manage accreditation credentials.
- **Typical users:** Accreditation Committee staff.
- **Default permission intent:** `accreditation-credential.issue`, `accreditation-credential.revoke`, `accreditation-credential.replace`.
- **Required assignment:** Committee assignment (Accreditation).
- **Allowed scopes:** Meet + Committee.
- **Sensitive capabilities:** Highest in the Accreditation domain (see [high-integrity-access-controls.md](high-integrity-access-controls.md#accreditation)).
- **Prohibited assumptions:** Does not automatically gain `access-scan.override` authority (ROLE-49/ROLE-43 territory) — issuance and override are deliberately separate (SOD-05).
- **Separation-of-duties concerns:** SOD-05.
- **Approval authority:** Approval–Certification (issuance/revocation).
- **Review requirement:** High.
- **Validation status:** Recommended, pending [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage).

### ROLE-48 — Access Control Operator
*(Consolidates "Access Control Operator" and the general "QR Scanner Operator" concept from the assignment example.)*
- **Category:** Committee (Access Validation, front-line)
- **Purpose:** Operate a scanning device at a specific access point.
- **Typical users:** Volunteers/staff assigned to gates, meal lines, billeting entry, transport boarding.
- **Default permission intent:** `access-scan.validate` (device- and shift-scoped).
- **Required assignment:** Venue + Device + Shift assignment ([Example 5](phase-0.3-access-and-assignment-architecture.md#example-5)).
- **Allowed scopes:** Venue + Device + Shift — the narrowest scope combination in the platform.
- **Sensitive capabilities:** High-volume offline operation (see [offline-authorization-model.md](offline-authorization-model.md)).
- **Prohibited assumptions:** Cannot override an access denial without a distinct override permission (SOD-05-adjacent).
- **Separation-of-duties concerns:** Override authority held separately (ROLE-43/47 territory, not ROLE-48).
- **Approval authority:** Operational only.
- **Review requirement:** Medium — but high-volume, so shift-based review is more practical than per-account review.
- **Validation status:** Recommended.

---

## Delegation Roles

### ROLE-49 — Delegation Head
- **Category:** Delegation
- **Purpose:** Represent and administer one delegation's participation.
- **Typical users:** School/delegation-appointed head.
- **Default permission intent:** `delegation.manage-roster`, `competition-entry.submit` (own delegation only).
- **Required assignment:** Delegation assignment.
- **Allowed scopes:** Delegation (own only — see Scope Evaluation Rules).
- **Sensitive capabilities:** Views own athletes' Confidential data.
- **Prohibited assumptions:** **Cannot** approve eligibility or certify results (SOD-01, SOD-02/03) — submission only.
- **Separation-of-duties concerns:** SOD-01.
- **Approval authority:** Self-service.
- **Review requirement:** Medium.
- **Validation status:** Recommended, pending [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy).

### ROLE-50 — Coach
*(Consolidates "Coach" and "Assistant Coach" — the assistant relationship is assignment metadata, not a separate role, consistent with the Technical Official consolidation rationale above. Also consolidates the "Coach Portal User" and "Team Manager" candidates — a Coach's self-service portal access is a permission bundle on this role, not a separate role.)*
- **Category:** Delegation
- **Purpose:** Register and manage athletes for specific sport(s)/team(s) within a delegation.
- **Typical users:** School coaches.
- **Default permission intent:** `athlete-registration.create` (own team/sport), `competition-entry.submit` (own team/sport).
- **Required assignment:** Delegation assignment, scoped to specific sport(s)/team(s).
- **Allowed scopes:** Delegation + Sport (assigned only).
- **Sensitive capabilities:** Views own athletes' Confidential data.
- **Prohibited assumptions:** Cannot manage another coach's team within the same delegation without an explicit additional assignment.
- **Separation-of-duties concerns:** SOD-01.
- **Approval authority:** Self-service.
- **Review requirement:** Low–Medium.
- **Validation status:** Recommended.

### ROLE-51 — School Coordinator
- **Category:** Delegation
- **Purpose:** School-level point of contact, potentially distinct from the meet-specific Delegation Head.
- **Typical users:** School staff.
- **Default permission intent:** Read access to school's delegation status.
- **Required assignment:** Organizational assignment (School) or Delegation assignment.
- **Allowed scopes:** School or Delegation.
- **Sensitive capabilities:** None beyond read access.
- **Prohibited assumptions:** May overlap with Delegation Head — **requires consolidation validation**.
- **Separation-of-duties concerns:** None named.
- **Approval authority:** Self-service.
- **Review requirement:** Low.
- **Validation status:** Deferred — likely consolidates into ROLE-49 pending real-world confirmation of whether the two are actually distinct people/functions.

---

## Public and Self-Service Roles

### ROLE-52 — Parent or Guardian User
- **Category:** Public/Self-Service
- **Purpose:** Verified-relationship access to a minor athlete's schedule/results, if implemented.
- **Typical users:** Parents/guardians.
- **Default permission intent:** Read-only, scoped to verified relationship(s).
- **Required assignment:** A verified-relationship record (not a traditional operational Assignment — see [identity-model.md, Section 10](identity-model.md#10-parent-or-guardian-relationship)).
- **Allowed scopes:** Own related Participant(s) only.
- **Sensitive capabilities:** None — strictly read-only, no operational authority.
- **Prohibited assumptions:** Cannot submit registrations or entries — that remains Coach/Delegation Head territory unless explicitly changed by future policy.
- **Separation-of-duties concerns:** None.
- **Approval authority:** Self-service (read only).
- **Review requirement:** Low.
- **Validation status:** Deferred — depends entirely on [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access).

### ROLE-53 — Media User
- **Category:** Public/Self-Service
- **Purpose:** Accredited media access to approved press materials/schedules, distinct from Media Committee staff (ROLE-46/35).
- **Typical users:** External journalists/media organizations.
- **Default permission intent:** Read-only access to approved public + press-kit content.
- **Required assignment:** Accreditation credential (Media category) — not a committee assignment.
- **Allowed scopes:** Public + approved press content only.
- **Sensitive capabilities:** None — must never see protected athlete information (Section 22 rule).
- **Prohibited assumptions:** Media accreditation does not grant any operational authority.
- **Separation-of-duties concerns:** None.
- **Approval authority:** Self-service.
- **Review requirement:** Low.
- **Validation status:** Optional — depends on whether media accreditation is scoped for the initial release ([Phase 0.1 stakeholder-register.md](../00-product/stakeholder-register.md)).

### Public Anonymous (not a role)
Per [identity-model.md, Section "Public Anonymous Identity"](identity-model.md#identity-categories), unauthenticated visitors are **not** modeled as a role — they are the default, read-only baseline state for the Public Information surface. See [phase-0.3-access-and-assignment-architecture.md, Section 22](phase-0.3-access-and-assignment-architecture.md#22-public-guest-and-self-service-access).

---

## Summary Table

| Role ID | Name | Category | Assignment Required | Status |
|---|---|---|---|---|
| ROLE-01 | Platform Super Administrator | Platform | No | Recommended |
| ROLE-02 | Platform Administrator | Platform | No | Recommended |
| ROLE-03 | Security Administrator | Platform | No | Recommended |
| ROLE-04 | Support Administrator | Platform | No | Requires validation |
| ROLE-05 | Audit Viewer | Platform | No | Recommended |
| ROLE-06 | Organization Administrator | Organization | Yes | Recommended |
| ROLE-07/08/09 | Regional/Division/School Administrator | Organization | Yes | Deferred |
| ROLE-10 | Meet Director | Meet Governance | Yes | Recommended |
| ROLE-11 | Meet Administrator | Meet Governance | Yes | Recommended |
| ROLE-12 | Executive Committee Member | Meet Governance | Yes | Requires validation |
| ROLE-13/14 | Secretariat Head / Staff | Meet Governance | Yes | Recommended |
| ROLE-15 | Meet Auditor | Meet Governance | Yes | Recommended |
| ROLE-16 | Meet Observer | Meet Governance | Yes | Optional |
| ROLE-17 | Delegation Registrar | Registration | Yes | Recommended |
| ROLE-18 | Registration Reviewer | Registration | Yes | Recommended |
| ROLE-19 | Eligibility Reviewer | Eligibility | Yes | Recommended |
| ROLE-20 | Eligibility Approver | Eligibility | Yes | **Blocking (OD-07)** |
| ROLE-21 | Medical Clearance Reviewer | Eligibility | Yes | Requires validation |
| ROLE-22 | Document Verifier | Eligibility | Yes | Optional |
| ROLE-23 | Sports Coordinator | Competition | Yes | Recommended |
| ROLE-24/25 | Tournament Manager / Assistant | Competition | Yes | Recommended |
| ROLE-26 | Technical Delegate | Competition | Yes | **Blocking (OD-09)** |
| ROLE-27 | Technical Official | Technical Officiating | Yes | Recommended |
| ROLE-28 | Result Validator | Results | Yes | Recommended |
| ROLE-29 | Result Certifier | Results | Yes | **Blocking (OD-08)** |
| ROLE-30/31/32 | Tally Encoder / Reviewer / Certifier | Tally | Yes | **Blocking (OD-12)** for Certifier |
| ROLE-33 | Result Publisher | Publication | Yes | Recommended |
| ROLE-34 | Public Information Publisher | Publication | Yes | Recommended |
| ROLE-35 | Media Content Manager | Publication | Yes | Optional |
| ROLE-36/37 | Committee Head / Staff (generic) | Committee | Yes | Recommended |
| ROLE-38/39 | Medical Officer / Staff | Committee | Yes | Requires validation |
| ROLE-40/41/42 | Billeting/Food/Transportation Coordinator | Committee | Yes | Recommended |
| ROLE-43 | Security Coordinator | Committee | Yes | Recommended |
| ROLE-44 | ICT Coordinator | Committee | Yes | Recommended |
| ROLE-45 | Finance Coordinator | Committee | Yes | Recommended |
| ROLE-46 | Media Coordinator | Committee | Yes | Recommended |
| ROLE-47 | Accreditation Officer | Committee | Yes | Recommended |
| ROLE-48 | Access Control Operator | Committee | Yes | Recommended |
| ROLE-49 | Delegation Head | Delegation | Yes | Recommended |
| ROLE-50 | Coach | Delegation | Yes | Recommended |
| ROLE-51 | School Coordinator | Delegation | Yes | Deferred |
| ROLE-52 | Parent or Guardian User | Public/Self-Service | Verified relationship | Deferred |
| ROLE-53 | Media User | Public/Self-Service | Accreditation | Optional |

53 roles total (before consolidation this space would plausibly have held 65+ candidates — the reduction is a deliberate application of the Role Design Rules, not an oversight).
