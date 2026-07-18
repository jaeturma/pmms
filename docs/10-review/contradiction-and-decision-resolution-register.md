# PMMS Contradiction and Decision Resolution Register

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [architecture-consistency-and-contradiction-analysis.md](architecture-consistency-and-contradiction-analysis.md), all 12 phase open-decision registers

This register consolidates the highest-priority unresolved or conflicting decisions across the entire 12-phase corpus, using ID prefix `DR-` (Decision Resolution). **This review found zero material architectural contradictions** (see [architecture-consistency-and-contradiction-analysis.md](architecture-consistency-and-contradiction-analysis.md)) — every entry below is an unresolved decision, not a conflict between two documented rules. Each entry references its original phase-specific ID rather than renumbering it.

---

### DR-01 — Eligibility Authority (OD-07)

- **Source phase:** 0.1
- **Question:** Which role/office holds final authority to approve or reject athlete eligibility?
- **Uncertainty:** Blocks SOD-01 enforcement and the entire Eligibility workflow (WF-04).
- **Options:** Per [../00-product/open-decisions.md, OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).
- **Recommended direction:** Resolve via direct DepEd consultation, prioritized alongside DR-04 (sports rule source) since both trace to the same PSG-04 source gap.
- **Evidence required:** DepEd policy confirmation.
- **Stakeholders:** DepEd Leadership, Sports-domain representative.
- **Blocking level:** Critical — blocking.
- **Target phase:** Pre-Eligibility-module implementation.
- **Status:** Open.

### DR-02 — Official Result Approval Chain (OD-08)

- **Source phase:** 0.1
- **Question:** What is the formal approval/certification chain for an Official Result?
- **Uncertainty:** Affects WF-11/WF-12 authorization design.
- **Options:** Per [../00-product/open-decisions.md, OD-08](../00-product/open-decisions.md).
- **Recommended direction:** Resolve alongside DR-01/DR-04, same DepEd engagement window.
- **Evidence required:** DepEd/sports-governing-body confirmation.
- **Stakeholders:** DepEd Leadership, Sports-domain representative.
- **Blocking level:** High.
- **Target phase:** Pre-Scoring/Official-Results-module implementation.
- **Status:** Open.

### DR-03 — Protest and Appeal Authority (OD-09)

- **Source phase:** 0.1
- **Question:** Who adjudicates protests/appeals, and within what time window?
- **Uncertainty:** Blocks SOD-03 and the Protest and Appeal workflow (WF-14).
- **Options:** Per [../00-product/open-decisions.md, OD-09](../00-product/open-decisions.md).
- **Recommended direction:** Same DepEd engagement window as DR-01.
- **Evidence required:** DepEd/sports-governing-body confirmation (PSG-15).
- **Stakeholders:** DepEd Leadership, Sports-domain representative.
- **Blocking level:** Critical — blocking.
- **Target phase:** Pre-Protest-module implementation.
- **Status:** Open.

### DR-04 — Sports Rule Source (OD-10)

- **Source phase:** 0.1
- **Question:** What is the authoritative source for each sport's rules, formats, and scoring?
- **Uncertainty:** Blocks every sport-specific implementation, recurring per sport.
- **Options:** Per [../00-product/open-decisions.md, OD-10](../00-product/open-decisions.md).
- **Recommended direction:** Prioritize the sports selected for the first pilot meet; do not attempt to resolve all sports simultaneously.
- **Evidence required:** Verified rulebook per sport (PSG-14).
- **Stakeholders:** Sports-domain representative, individual sports governing bodies.
- **Blocking level:** Critical — blocking, per-sport.
- **Target phase:** Pre-sport-specific implementation, per sport, prioritized by pilot sport selection.
- **Status:** Open.

### DR-05 — Medal Tally Rules (OD-12)

- **Source phase:** 0.1
- **Question:** What are the official medal-tally computation and tie-breaking rules?
- **Uncertainty:** Blocks SOD-04 and Medal Tally workflow (WF-15).
- **Options:** Per [../00-product/open-decisions.md, OD-12](../00-product/open-decisions.md).
- **Recommended direction:** Same engagement window as DR-01/03/04.
- **Evidence required:** Verified rule source (PSG-16).
- **Stakeholders:** DepEd Leadership, Sports-domain representative.
- **Blocking level:** Critical — blocking.
- **Target phase:** Pre-Medal-Tally-module implementation.
- **Status:** Open.

### DR-06 — Medical-Data Handling (OD-15)

- **Source phase:** 0.1
- **Question:** What is the formal medical-data handling authority and protocol?
- **Uncertainty:** Blocks SOD-09, Phase 0.10's AX-12, and Medical Operations' core policy.
- **Options:** Per [../00-product/open-decisions.md, OD-15](../00-product/open-decisions.md).
- **Recommended direction:** Requires legal/privacy input in addition to DepEd, given medical-data sensitivity — a separate, likely longer, engagement track than DR-01/03/04/05.
- **Evidence required:** Verified policy source (PSG-05), legal/privacy review.
- **Stakeholders:** DepEd Leadership, Privacy reviewer, Medical Team lead.
- **Blocking level:** Critical — blocking.
- **Target phase:** Pre-Medical-module implementation.
- **Status:** Open.

### DR-07 — Single-Organization Versus Multi-Organization (OD-02)

- **Source phase:** 0.1
- **Question:** Is PMMS built solely for DepEd, or must it support multiple organizations from the start?
- **Uncertainty:** Determines whether multi-tenancy (Phase 0.12) is ever activated.
- **Options:** Per [../00-product/open-decisions.md, OD-02](../00-product/open-decisions.md).
- **Recommended direction:** Single-organization at launch, tenant-isolation-ready architecture — restated unchanged as the correct, already-adopted direction throughout Phase 0.5–0.12.
- **Evidence required:** Confirmation of near-term non-DepEd organizational interest.
- **Stakeholders:** DepEd Leadership.
- **Blocking level:** Low near-term (the recommended direction is already the working assumption); High if the answer changes.
- **Target phase:** Ongoing; formally confirm before any Stage 4 multi-tenancy activation.
- **Status:** Open, non-blocking for Phase 1.

### DR-08 — Single-Meet Versus Multi-Meet Launch (OD-03)

- **Source phase:** 0.1
- **Question:** Does PMMS launch supporting one meet or several concurrently?
- **Uncertainty:** Affects Phase 1 scope sizing directly.
- **Options:** Per [../00-product/open-decisions.md, OD-03](../00-product/open-decisions.md).
- **Recommended direction:** Single-meet for the initial pilot, consistent with the Minimum Viable Product boundary in the main review document.
- **Evidence required:** Product-owner confirmation of pilot meet selection (QD-13).
- **Stakeholders:** Product owner.
- **Blocking level:** High for Phase 1 scoping.
- **Target phase:** Immediately, feeds Phase 0.14 scope definition.
- **Status:** Open.

### DR-09 — Participant Identity Modeling (DD-01)

- **Source phase:** 0.2
- **Question:** Is Participant Registry unified or split by role (Athlete/Coach/Official)?
- **Uncertainty:** Affects five bounded contexts simultaneously; restated as [GAP-12](architecture-gap-register.md).
- **Options:** Per [../01-architecture/domain-open-decisions.md, DD-01](../01-architecture/domain-open-decisions.md).
- **Recommended direction:** Resolve before physical schema design begins (must precede GAP-01).
- **Evidence required:** None blocking — an architectural judgment call.
- **Stakeholders:** Lead architect, Domain reviewer.
- **Blocking level:** Critical — blocking Phase 0.14's first work package.
- **Target phase:** Immediately, before Phase 0.14 schema work.
- **Status:** Open.

### DR-10 — Tenant Boundaries (DD-21) and Tenant-Column Timing (PD-01) — Already Resolved in Direction

- **Source phase:** 0.2, 0.5
- **Question:** Should organization-scoping be designed in from the start, even for a single-organization launch?
- **Uncertainty:** None remaining — both DD-21 and PD-01 recommend "yes, at low cost," and Phase 0.12 confirms this as the adopted direction.
- **Options:** N/A — resolved in direction, only implementation remains.
- **Recommended direction:** Confirmed: nullable `organization_id`/tenant key from the first migration.
- **Evidence required:** None blocking.
- **Stakeholders:** Lead architect.
- **Blocking level:** None — this decision is effectively closed pending only implementation.
- **Target phase:** Phase 0.14 schema design.
- **Status:** Resolved in direction; implementation pending.

### DR-11 — Deployment Topology and Cloud-Versus-On-Premise (DV-01)

- **Source phase:** 0.8
- **Question:** Where and how is PMMS hosted?
- **Uncertainty:** Blocks five further DevOps decisions and two Phase 0.12 decisions (restated [GAP-04](architecture-gap-register.md)).
- **Options:** Per [../05-devops/devops-open-decisions.md, DV-01](../05-devops/devops-open-decisions.md).
- **Recommended direction:** Begin with the confirmed pilot-scale single-server topology regardless of eventual cloud-versus-on-premise answer — this decision does not need to block Phase 1 application-code work, only deployment planning.
- **Evidence required:** Budget and DepEd IT governance confirmation (PSG-10).
- **Stakeholders:** DepEd Leadership, Infrastructure owner.
- **Blocking level:** High for deployment/pilot, Low for application-code implementation.
- **Target phase:** Pre-pilot-deployment planning.
- **Status:** Open.

### DR-12 — RPO/RTO Numeric Targets (RD-18 → PD-23 → SD-24 → DV-17 → ED-33)

- **Source phase:** 0.4 (originated), carried through 0.5/0.6/0.8/0.12
- **Question:** What are PMMS's specific recovery point and recovery time objectives?
- **Uncertainty:** The longest-carried open decision in the entire architecture.
- **Options:** Per [../05-devops/devops-open-decisions.md, DV-17](../05-devops/devops-open-decisions.md).
- **Recommended direction:** Set only after Stage 2 pilot operational data exists — restated unchanged across every phase that has touched this decision.
- **Evidence required:** Pilot operational data (backup duration, restore duration, incident patterns).
- **Stakeholders:** Infrastructure owner, Security owner, DepEd Leadership.
- **Blocking level:** High for production, Low for Phase 1/pilot.
- **Target phase:** Post-pilot.
- **Status:** Open.

### DR-13 — Outbox Pattern Adoption Trigger (RD-01 → PD-21 → WD-08)

- **Source phase:** 0.4, carried through 0.5/0.11
- **Question:** Is a formal transactional outbox table adopted, or does `after_commit` dispatch suffice?
- **Uncertainty:** Affects delivery-reliability guarantee for every critical cross-context event.
- **Options:** Per [../08-workflows/workflow-open-decisions.md, WD-08](../08-workflows/workflow-open-decisions.md#wd-08--outbox-table-versus-after_commit-dispatch).
- **Recommended direction:** A targeted technical spike early in Phase 0.14, before Official Results/Medal Tally implementation.
- **Evidence required:** Load-tested comparison under realistic conditions.
- **Stakeholders:** Lead architect, Engineering lead.
- **Blocking level:** High, specifically for Official Results/Medal Tally/Accreditation modules.
- **Target phase:** Phase 0.14, early.
- **Status:** Open.

### DR-14 — WCAG Conformance Target (DX-01)

- **Source phase:** 0.9
- **Question:** What accessibility conformance level does PMMS target?
- **Uncertainty:** Blocks every accessibility-related design-quality gate.
- **Options:** Per [../06-design/design-open-decisions.md, DX-01](../06-design/design-open-decisions.md).
- **Recommended direction:** WCAG 2.1 AA is the conventional government/public-sector target — recommend as the starting evaluation point, pending formal DepEd accessibility-policy confirmation (PSG-13).
- **Evidence required:** DepEd accessibility standard, if one exists.
- **Stakeholders:** DepEd Leadership, UX lead, Accessibility specialist.
- **Blocking level:** High.
- **Target phase:** Pre-Phase-1 UI implementation.
- **Status:** Open.

### DR-15 — First AI Pilot Use-Case Selection (AX-01)

- **Source phase:** 0.10
- **Question:** Which of the 13 candidate AI capabilities, if any, is piloted first?
- **Uncertainty:** No AI capability can proceed without this.
- **Options:** Per [../07-ai/ai-open-decisions.md, AX-01](../07-ai/ai-open-decisions.md#ax-01--first-pilot-use-case-selection).
- **Recommended direction:** Defer entirely until Phase 1 foundation and first pilot are complete, consistent with this review's Priority 5 classification of all AI capabilities.
- **Evidence required:** None blocking — a sequencing decision.
- **Stakeholders:** AI governance owner, Product owner.
- **Blocking level:** None for Phase 1 (correctly deferred).
- **Target phase:** Post-pilot.
- **Status:** Open, deferred by design.

## Summary of Blocking Decisions Requiring Immediate Attention

| Decision | Blocking Level | Target |
|---|---|---|
| DR-09 (participant identity, DD-01) | Critical | Immediately, before Phase 0.14 schema work |
| DR-01/DR-03/DR-04/DR-05/DR-06 (OD-07/09/10/12/15 cluster) | Critical | Single coordinated DepEd/sports engagement, pre-module implementation |
| DR-08 (single-vs-multi-meet, OD-03) | High | Immediately, feeds Phase 1 scope |
| DR-13 (outbox pattern, WD-08) | High | Phase 0.14, early technical spike |
| DR-14 (WCAG target, DX-01) | High | Pre-Phase-1 UI implementation |
