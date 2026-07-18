# PMMS Workflow Incident, Change, and Release Governance

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../05-devops/incident-problem-change-and-release-management.md](../05-devops/incident-problem-change-and-release-management.md) · [../07-ai/ai-incident-response-change-and-release-governance.md](../07-ai/ai-incident-response-change-and-release-governance.md)

---

## 1. Workflow and Automation Incident Categories

Extending [../05-devops/incident-problem-change-and-release-management.md, Section 1](../05-devops/incident-problem-change-and-release-management.md#1-incident-management) and [../07-ai/ai-incident-response-change-and-release-governance.md, Section 1](../07-ai/ai-incident-response-change-and-release-governance.md#1-ai-incident-response) with workflow-specific categories: a stuck/stalled long-running process · a runaway automation (repeatedly firing beyond its intended scope) · a notification storm · a missed critical event (a `ResultCertified` that never reached Medal Tally) · a queue backlog breach · a SOD-rule bypass discovered in audit review · a mis-scheduled timer producing incorrect deadlines.

## 2. Automation Incident Response

Every automation incident follows the same lifecycle already established (`Detect → Acknowledge → Classify → Triage → Contain → Communicate → Resolve → Recover → Verify → Review → Improve`) — the primary containment action for a runaway or misbehaving automation is **immediate feature-flag disablement**, restated from [responsible-automation-and-authority-boundaries.md, Section 7](responsible-automation-and-authority-boundaries.md#7-automation-feature-flags-disablement-and-rollback), directly mirroring [../07-ai/ai-incident-response-change-and-release-governance.md, Section 1](../07-ai/ai-incident-response-change-and-release-governance.md#1-ai-incident-response)'s identical AI-disablement discipline extended to non-AI automation.

## 3. Workflow and Automation Release Gates

Before any workflow definition or automation entry reaches production: approved workflow/automation definition · risk tier assigned (per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification)) · owning-role approval · security review (for High-Risk) · SOD verification · idempotency verification · failure-mode testing · authorization testing · observability confirmed · feature flag in place · rollback/disablement plan confirmed · user guidance prepared (where user-facing) · manual-fallback path confirmed.

**No workflow or automation bypasses these gates, regardless of its risk tier** — restated absolutely, mirroring [../07-ai/ai-incident-response-change-and-release-governance.md, Section 3](../07-ai/ai-incident-response-change-and-release-governance.md#3-ai-release-gates)'s identical "no bypass regardless of tier" principle.

## 4. Workflow Change Management

A workflow-definition change or automation-entry change follows the same change-classification discipline as [../05-devops/incident-problem-change-and-release-management.md, Section 5](../05-devops/incident-problem-change-and-release-management.md#5-change-management), with the active-instance compatibility requirements from [workflow-versioning-migration-and-active-instance-compatibility.md, Section 3](workflow-versioning-migration-and-active-instance-compatibility.md#3-active-instance-compatibility) applied before any change ships.

## 5. Rollback and Forward-Fix

A workflow/automation "rollback" via feature-flag disablement is definitionally simpler and faster than a full application-code deployment rollback, restated from [../05-devops/deployment-strategies-rollbacks-and-maintenance.md, Section 4](../05-devops/deployment-strategies-rollbacks-and-maintenance.md#4-rollback-architecture) and [../07-ai/ai-incident-response-change-and-release-governance.md, Section 5](../07-ai/ai-incident-response-change-and-release-governance.md#5-rollback-and-forward-fix)'s identical discipline. A workflow-definition schema change (versus a mere flag toggle) follows the same phased, backward-compatible pattern as any database schema change, restated from [workflow-versioning-migration-and-active-instance-compatibility.md, Section 3](workflow-versioning-migration-and-active-instance-compatibility.md#3-active-instance-compatibility).

## 6. Workflow Acceptance Criteria (Per Definition)

Every workflow document in this package (Sections covering WF-01 through WF-25) is considered release-ready only once its full field set (per [workflow-vision-principles-and-governance.md, Section 4](workflow-vision-principles-and-governance.md#4-workflow-ownership)) is populated with no "TBD" in a required field, its state machine has been reviewed against [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) where applicable, and — for any High-Risk workflow — its SOD rule has been confirmed structurally enforceable or explicitly flagged as audit-detectable-only pending implementation.

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-32 (whether a dedicated workflow-incident severity matrix is adopted or the existing general/AI severity models suffice, mirroring Phase 0.10's identical AX-20 question).
