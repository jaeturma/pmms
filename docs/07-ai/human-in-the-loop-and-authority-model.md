# PMMS Human-in-the-Loop and Authority Model

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) · [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

This document defines the human-in-the-loop lifecycle every AI capability follows, and the authority model each capability document must specify. **No workflow implementation is created here.**

---

## 1. Human-in-the-Loop Lifecycle

```text
User Request
→ Authorization Check
→ Data Minimization
→ AI Processing
→ Grounded Output
→ Confidence and Limitation Display
→ Human Review
→ Accept, Reject, Edit, or Escalate
→ Authorized Application Action
→ Audit
```

**AI outputs never directly update authoritative state** — restated absolutely; the "Authorized Application Action" step is always a human-initiated action passing through the platform's ordinary Command/Application-layer path (per [../01-architecture/laravel-architecture.md, Section 3](../01-architecture/laravel-architecture.md#3-command-and-query-architecture)), identical to what would happen if a human had acted without AI involvement.

## 2. Stage Detail

| Stage | Requirement |
|---|---|
| Authorization check | The requesting user's role, permission, assignment, and scope are confirmed before any AI processing begins — restated from [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) |
| Data minimization | Only the data the specific request needs is assembled — per [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md) |
| AI processing | Occurs entirely within the AI Gateway's controlled boundary, per [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md) |
| Grounded output | The AI response cites its evidence — restated from [ai-explainability-confidence-and-user-review.md](ai-explainability-confidence-and-user-review.md) |
| Confidence and limitation display | Never omitted, never overstated |
| Human review | A named reviewer role examines the output before any downstream effect |
| Accept, reject, edit, or escalate | Four possible reviewer dispositions — never a fifth silent "ignore" path that leaves the finding unresolved |
| Authorized application action | Only if accepted (possibly edited); uses the exact same workflow a human-originated action would use |
| Audit | Every stage above is recorded, per [ai-identity-authorization-scope-and-audit.md](ai-identity-authorization-scope-and-audit.md) |

## 3. Decision Ownership

The human reviewer named in each capability's authority table (Section, [Document 8 in the main document](phase-0.10-ai-assisted-platform-architecture.md#8-ai-authority-model)) is the accountable decision-owner for that capability's output — restated absolutely, an AI capability never has an ambiguous or unnamed reviewer.

## 4. Acceptance, Editing, Rejection, and Escalation

| Disposition | Meaning |
|---|---|
| Accept | The reviewer agrees with the AI output and proceeds to the authorized action using it as-is |
| Edit | The reviewer modifies the AI output before proceeding — the edit itself is recorded, distinguishing AI-original content from human-modified content |
| Reject | The reviewer disagrees; no downstream action occurs; the rejection and its reason are recorded, feeding evaluation (per [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md)) |
| Escalate | The reviewer lacks sufficient confidence or authority to decide; the case routes to a higher-authority reviewer, per the domain's ordinary escalation path |

## 5. Separation of Duties for AI-Assisted Actions

Restated absolutely from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) — an AI suggestion never bypasses an existing SoD conflict. If SOD-01 prohibits the same individual from submitting and approving an eligibility case, an AI-assisted eligibility review still requires a distinct approver, exactly as an unassisted review would.

## 6. Accountability

The human who accepts, edits, or acts on an AI output is accountable for that action — restated absolutely from [ai-vision-principles-and-governance.md, Section 5](ai-vision-principles-and-governance.md#5-human-accountability). AI involvement is visible in the audit trail (per Section 2 above) but never dilutes or transfers the human's accountability.

## 7. Review Fatigue

A named operational risk: if AI-assisted review becomes routine, reviewers may begin rubber-stamping AI suggestions without genuine scrutiny, silently eroding the human-in-the-loop guarantee this entire model depends on. Mitigations (evaluated, not committed to a specific mechanism): periodic override-rate monitoring (per [ai-observability-cost-quotas-and-operations.md](ai-observability-cost-quotas-and-operations.md)), sampling-based quality audits of accepted suggestions, and deliberately varying presentation to avoid pattern-matching complacency.

## 8. Prohibited Automatic Actions (Cross-Reference)

Restated absolutely from [ai-vision-principles-and-governance.md, Section 4](ai-vision-principles-and-governance.md#4-prohibited-actions-absolute) and [ai-use-case-and-risk-classification.md, "Prohibited Autonomous Actions"](ai-use-case-and-risk-classification.md#prohibited-autonomous-actions-no-tier--always-human-only) — no human-in-the-loop stage above ever short-circuits to bypass these.

## 9. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific review-fatigue monitoring mechanism and whether escalation routes to a fixed higher-authority role or a configurable one per use case.
