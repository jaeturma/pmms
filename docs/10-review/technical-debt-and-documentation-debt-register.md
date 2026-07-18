# PMMS Technical Debt and Documentation Debt Register

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [architecture-gap-register.md](architecture-gap-register.md)

This register classifies debt using ID prefix `TD-`, distinguishing intentional architectural debt (a deliberate, evidence-based deferral) from unintentional debt (a gap that should be closed but has not been). Every entry includes: Debt ID, Description, Source, Impact, Risk, Reason accepted, Owner to be identified, Target phase, Resolution trigger, Status.

---

### TD-01 — Physical Database Schema Deferred (Architecture Debt)

- **Description:** No physical schema exists; deferred across three consecutive phase-projection failures.
- **Source:** Phase 0.5 → 0.6 → 0.7 projection drift, restated in [architecture-completeness-assessment.md, Section 3](architecture-completeness-assessment.md#3-the-physical-database-schema-gap-critical-finding).
- **Impact:** Blocks Phase 1 backlog decomposition for every data-touching module.
- **Risk:** High if not resolved as the explicit first Phase 0.14 work package.
- **Reason accepted:** Unintentional — a genuine cross-phase coordination gap, not a deliberate deferral.
- **Owner:** To be identified (Lead architect).
- **Target phase:** Phase 0.14 (first work package).
- **Resolution trigger:** Phase 0.14 kickoff.
- **Status:** Open — unintentional debt, highest priority.

### TD-02 — Zero Verified Policy/Sports-Rule Sources (Policy Debt)

- **Description:** All 13 POL entries and every sports rulebook remain unverified.
- **Source:** [policy-rulebook-and-source-validation-gap-register.md](policy-rulebook-and-source-validation-gap-register.md).
- **Impact:** Blocks eligibility, protest, medal-tally, medical, retention, and sport-specific modules.
- **Risk:** High — external dependency, not resolvable by architecture work alone.
- **Reason accepted:** Intentional (correct discipline: never invent a policy) but the underlying gap itself is unintentional and overdue.
- **Owner:** To be identified (DepEd Leadership, Sports-domain representative).
- **Target phase:** Ongoing, per affected module.
- **Resolution trigger:** DepEd/sports-governing-body engagement.
- **Status:** Open.

### TD-03 — No Implementation Exists (Implementation Debt — Expected)

- **Description:** No Laravel domain module, migration, model, controller, test, React component, or Flutter widget exists for any PMMS business capability.
- **Source:** Repository inspection, this review and every prior phase.
- **Impact:** None yet — this is the expected state at the end of a documentation-only Phase 0.
- **Risk:** Low, provided Phase 0.14 proceeds with the readiness this review confirms exists.
- **Reason accepted:** Intentional — Phase 0's entire scope was architecture, not implementation, per working rule 6 of every phase from 0.1 through 0.13.
- **Owner:** To be identified (Engineering lead).
- **Target phase:** Phase 0.14 onward.
- **Resolution trigger:** Phase 0.14 begins.
- **Status:** Expected, not a defect.

### TD-04 — No Test Exists Beyond Default Starter-Kit Tests (Test Debt — Expected)

- **Description:** 14 confirmed PHP test files, all pre-dating Phase 0.1, unmodified.
- **Source:** Repository inspection.
- **Impact:** None yet.
- **Risk:** Low, same reasoning as TD-03.
- **Reason accepted:** Intentional — Phase 0.7 defined test *strategy*, not test *code*, per its own working rules.
- **Owner:** To be identified (QA lead).
- **Target phase:** Phase 0.14 onward, following Phase 0.7's strategy.
- **Resolution trigger:** Phase 0.14 begins.
- **Status:** Expected, not a defect.

### TD-05 — Role-Category Count and Permission-Count Documentation Inconsistencies (Documentation Debt)

- **Description:** Restated from [architecture-consistency-and-contradiction-analysis.md, Section 9](architecture-consistency-and-contradiction-analysis.md#9-documentation-inconsistencies-found-distinct-from-architecture-contradictions).
- **Source:** Phase 0.3 primary document and permission catalog.
- **Impact:** Cosmetic only.
- **Risk:** Low.
- **Reason accepted:** Unintentional — a minor drafting inconsistency.
- **Owner:** To be identified (Domain reviewer).
- **Target phase:** Opportunistic.
- **Resolution trigger:** Next Phase 0.3 documentation touch.
- **Status:** Open, low priority.

### TD-06 — WF-24/WF-25 Not Back-Ported to Phase 0.2 Catalog (Documentation Debt)

- **Description:** Restated from [GAP-14](architecture-gap-register.md).
- **Source:** Phase 0.11.
- **Impact:** Minor catalog-navigability issue.
- **Risk:** Low.
- **Reason accepted:** Unintentional — Phase 0.11 correctly extended rather than modified Phase 0.2's file, per working rule 4, but never circled back.
- **Owner:** To be identified (Domain reviewer).
- **Target phase:** Next Phase 0.2 revision opportunity.
- **Resolution trigger:** Opportunistic.
- **Status:** Open, low priority.

### TD-07 — All 27 UX Proto-Personas Unvalidated (UX Debt)

- **Description:** Restated from [GAP-08](architecture-gap-register.md).
- **Source:** Phase 0.9.
- **Impact:** UX decisions rest on unvalidated assumptions.
- **Risk:** Moderate — could require rework if pilot reveals materially different user needs.
- **Reason accepted:** Intentional — Phase 0.9 correctly labeled these "proto-personas requiring validation" rather than fabricating validation.
- **Owner:** To be identified (UX lead).
- **Target phase:** First pilot.
- **Resolution trigger:** Pilot user research.
- **Status:** Open, intentional debt with a clear resolution trigger.

### TD-08 — No AI Provider, Model, or Evaluation Dataset Selected (AI Debt)

- **Description:** Restated from [GAP-10](architecture-gap-register.md).
- **Source:** Phase 0.10.
- **Impact:** No AI capability can be piloted.
- **Risk:** Low near-term (AI is explicitly Priority 5), would become High if AI were prematurely prioritized.
- **Reason accepted:** Intentional — Phase 0.10's own explicit "no provider selected" discipline.
- **Owner:** To be identified (AI governance owner).
- **Target phase:** Post-Phase-1-foundation, post-pilot.
- **Resolution trigger:** AX-01 (first pilot use-case selection).
- **Status:** Open, intentional debt, correctly deferred.

### TD-09 — No Restore Drill Has Ever Been Performed (Operational Debt)

- **Description:** Restated from [ARR-08](architecture-risk-register.md).
- **Source:** Phase 0.5, 0.8, 0.12.
- **Impact:** Cannot claim any DR capability above Documented evidence level.
- **Risk:** High if a real disaster occurs before the first drill.
- **Reason accepted:** Unintentional in the sense that no phase performed one (none could, being documentation-only) — but intentional in that Phase 0 correctly never claimed otherwise.
- **Owner:** To be identified (Infrastructure owner).
- **Target phase:** Stage 2 pilot readiness.
- **Resolution trigger:** First restore drill scheduled and executed.
- **Status:** Open.

### TD-10 — Enterprise-Readiness Documentation Volume Relative to Current Scope (Enterprise Debt)

- **Description:** 34 Phase 0.12 supporting documents describe Stage 4–6 capabilities while PMMS is confirmed Stage 1.
- **Source:** Phase 0.12.
- **Impact:** Restated from [ARR-05](architecture-risk-register.md) — a scope-creep risk if misread, not itself a defect.
- **Risk:** Low, given this review's own mitigating remediation roadmap.
- **Reason accepted:** Intentional — Phase 0.1's Section 18 explicitly directed commercial-quality/tenant-isolation readiness "from the outset, at low cost."
- **Owner:** To be identified (Product owner).
- **Target phase:** Referenced, never activated, until Stage 4+ evidence exists.
- **Resolution trigger:** A specific multi-organization or commercial customer materializes.
- **Status:** Open, intentional debt, correctly bounded.

### TD-11 — Security and Privacy Debt Cannot Be Hidden (Explicit Non-Finding)

Per this register's own governing rule (restated from [.ai/technical-debt-rules.md](../../.ai/technical-debt-rules.md)): **no security or privacy debt item was found to be hidden or unacknowledged anywhere in the 12-phase corpus.** Every security/privacy gap identified in this review (TD-02 policy sources, ARR-10 minor-athlete exposure risk) is explicitly named, not concealed. This is itself a positive finding, recorded here for completeness.

## Summary by Debt Category

| Category | Count | Highest-Severity Item |
|---|---|---|
| Architecture debt | 1 | TD-01 (physical schema) |
| Policy debt | 1 | TD-02 |
| Implementation debt | 1 | TD-03 (expected) |
| Test debt | 1 | TD-04 (expected) |
| Documentation debt | 2 | TD-05, TD-06 |
| UX debt | 1 | TD-07 |
| AI debt | 1 | TD-08 |
| Operational debt | 1 | TD-09 |
| Enterprise debt | 1 | TD-10 |
| Security/privacy debt | 0 explicit items (see TD-11) | N/A |
