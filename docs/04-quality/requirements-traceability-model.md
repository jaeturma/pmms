# PMMS Requirements Traceability Model

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-engineering-strategy.md](quality-engineering-strategy.md) · [../00-product/product-scope.md](../00-product/product-scope.md) · [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md)

This document defines the traceability chain from product objective through to release decision, and the standards acceptance criteria must meet. **No traceability tooling is created here.**

---

## 1. Traceability Chain

```text
Product Objective
→ Business Capability
→ Bounded Context
→ Requirement
→ Business Rule
→ Risk
→ Control
→ Acceptance Criterion
→ Test Scenario
→ Test Evidence
→ Release Decision
```

| Link | Source | Example |
|---|---|---|
| Product Objective | [../00-product/phase-0.1-product-foundation.md](../00-product/phase-0.1-product-foundation.md) | "Reliable, trustworthy official results" |
| Business Capability | [../01-architecture/business-capability-map.md](../01-architecture/business-capability-map.md) | "Result Certification" |
| Bounded Context | [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) | BC-16 Official Results |
| Requirement | A specific feature/work-item description | "Certify a validated score set into an official result" |
| Business Rule | Source-cited sports/eligibility/scoring rule, or an architectural rule (e.g., SOD-02) | "Certifier ≠ validator" |
| Risk | [risk-based-testing-model.md](risk-based-testing-model.md) | Critical tier |
| Control | [../03-security/compliance-control-framework.md, Section 2](../03-security/compliance-control-framework.md#2-control-catalog) | CTL-02 (least privilege), CTL-04 (audit) |
| Acceptance Criterion | Section 2 below | "Given an unvalidated score set, When certification is attempted, Then it is rejected with an audit-logged denial" |
| Test Scenario | [test-levels-and-test-types.md](test-levels-and-test-types.md) | Feature test + negative-path test |
| Test Evidence | [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md) | Test report, audit-event capture |
| Release Decision | [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) | Approved / Conditionally approved / Rejected |

## 2. Traceability Identifiers (Conceptual)

Each link in the chain carries an identifier from its originating phase's numbering scheme where one exists — BC-XX (bounded contexts), WF-XX (workflows), ROLE-XX/permission entries, SOD-XX, CTL-XX (controls), SR-XX (security risks), PD-XX/SD-XX (open decisions) — plus a new **AC-XX** (Acceptance Criterion) and **TS-XX** (Test Scenario) identifier space this phase introduces. No physical tracking system (issue tracker, requirements-management tool) is selected here — this is the conceptual model a future tool implements.

## 3. Traceability Ownership

The Domain owner (per bounded context) is accountable for the Requirement → Business Rule → Risk portion of the chain; the QA lead is accountable for the Acceptance Criterion → Test Scenario → Test Evidence portion; the Release approver confirms the full chain is intact before a Release Decision, per [quality-governance-and-ownership.md](quality-governance-and-ownership.md).

## 4. Acceptance Criteria Standard

Every acceptance criterion must be:

| Property | Meaning |
|---|---|
| Observable | Describable in terms of an outcome that can actually be checked |
| Testable | Expressible as a pass/fail test scenario |
| Unambiguous | Not open to multiple reasonable interpretations |
| Role-aware | States which role(s) the behavior applies to |
| Scope-aware | States which organization/meet/committee scope it applies within |
| State-aware | States the resource state(s) the behavior depends on |
| Data-aware | States what data is involved and its classification implications |
| Security-aware | States the authorization requirement |
| Error-aware | States the expected behavior on invalid input or denied access |
| Recovery-aware | States expected behavior after a failure/conflict, where relevant |
| Accessible where user-facing | States accessibility expectations for any UI-facing criterion |
| Traceable to source rules | Cites the specific business-rule source (sports rule, policy, architectural rule) it derives from — never an invented rule |

### Structured Format (Where Appropriate)

```text
Given <a specific state/context>
When <a specific action>
Then <a specific, observable outcome>
```

**Gherkin/Given-When-Then is not required for every work item** — it is a useful structure for behaviorally complex or ambiguity-prone criteria (most Critical/High-tier work), not a mandatory format for every low-risk administrative change.

## 5. Definition of Ready

A work item is ready for implementation only when:

- Scope is clear.
- The bounded-context owner is clear.
- The business-rule source is identified (or explicitly marked pending source validation).
- Acceptance criteria exist, meeting Section 4's standard.
- Authorization implications are known (which permission/scope/assignment/SoD rules apply).
- Data-classification implications are known.
- Audit requirements are known (which audit-event category applies).
- Error and conflict behavior are defined.
- Test-data needs are understood.
- Dependencies (on other work items, contexts, or open decisions) are identified.
- UI designs exist where the item is user-facing.
- Open high-risk questions are resolved or explicitly, consciously accepted as a known limitation.

## 6. Definition of Done

A work item is done only when:

- Implementation is complete.
- Tests pass (at the levels appropriate to its risk tier, per [risk-based-testing-model.md, Section 4](risk-based-testing-model.md#4-test-depth-consequence-by-tier)).
- Authorization is verified (positive and negative cases).
- Audit behavior is verified.
- Sensitive-data behavior is verified (classification-appropriate handling).
- Negative paths are tested.
- Concurrency or conflict behavior is tested where relevant to the item's risk tier.
- Documentation is updated (this includes the relevant architecture/data/security documentation if the item changes an established rule).
- Static analysis passes.
- No unresolved Critical defect remains against this item.
- Required manual validation (UAT/domain-expert review, where applicable) is complete.
- Acceptance criteria are met.
- Release evidence is available, per [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md).

## 7. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably which physical tool (issue tracker, requirements-management system) implements this traceability chain, and whether AC-XX/TS-XX identifiers are formally assigned per work item or remain an informal discipline.
