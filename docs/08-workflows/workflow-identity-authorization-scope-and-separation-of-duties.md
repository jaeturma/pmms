# PMMS Workflow Identity, Authorization, Scope, and Separation of Duties

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) · [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md)

---

## 1. Workflow Authorization

Every workflow transition — human-initiated, scheduled, or automated — passes through the same 16-step authorization decision sequence defined in [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md), deny-by-default, with explicit denial or a security hold always taking precedence over any grant. **A workflow engine, scheduler, or automation entry is never a separate, lesser-scrutinized authorization path** — restated absolutely.

## 2. Workflow Scope

A workflow transition's scope is exactly the actor's (human, service, or automation) assignment scope, per [../01-architecture/scope-model.md](../01-architecture/scope-model.md) — Organization scope never implicitly grants Meet authority, Meet scope never implicitly grants Sport authority, and Committee scope never crosses committees, restated unchanged from Phase 0.3's non-inheritance rules.

## 3. Separation of Duties (Cross-Reference)

Full detail: [human-tasks-approvals-reviews-and-certifications.md, Section 3](human-tasks-approvals-reviews-and-certifications.md#3-separation-of-duties-applied-to-workflows). Every state-machine transition (per [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture)) names its applicable SOD-XX entry from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md), or explicitly states "None."

## 4. Conflict of Interest (Cross-Reference)

Full detail: [human-tasks-approvals-reviews-and-certifications.md, Section 4](human-tasks-approvals-reviews-and-certifications.md#4-conflict-of-interest).

## 5. Assignment Validity

A workflow transition's authorization is re-evaluated at execution time, not merely at task-creation time — restated from [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) ("jobs must recheck applicable authorization or execution authority"), extended to every workflow transition regardless of whether it executes synchronously or via a queued job. An assignment that expired between task creation and task completion invalidates the pending action.

## 6. Device Trust

A device-originated workflow action (e.g., an offline access scan) carries the Device Identity's own trust state (per [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md)) in addition to its human operator's identity — **device trust never substitutes for operator identity**, restated absolutely from [../06-design/accreditation-qr-device-and-shared-station-experience.md, Section 3](../06-design/accreditation-qr-device-and-shared-station-experience.md#3-shared-device-and-station-experience). A lost or revoked device rejects its next sync attempt outright, restated from [../01-architecture/offline-sync-runtime-architecture.md, Section 3](../01-architecture/offline-sync-runtime-architecture.md#3-runtime-rules).

## 7. Service Identities

Two categories of non-human workflow actor exist, and are never conflated:

| Identity Type | Scope | Restated From |
|---|---|---|
| Automation service identity | Restricted to its automation entry's declared permission and scope (Section 3, [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model)) | [../01-architecture/identity-model.md](../01-architecture/identity-model.md)'s Service Identity layer |
| AI service identity | Restricted per the intersection-not-union model | [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 2](../07-ai/ai-identity-authorization-scope-and-audit.md#2-ai-service-identity) |

**Neither service identity type ever holds a standing, unrestricted write credential** — restated absolutely from [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 34](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#34-ai-service-integration-boundary), generalized from AI specifically to every automated workflow actor.

## 8. Workflow Actor Attribution Table

Every workflow audit record (per [workflow-audit-observability-metrics-and-support.md, Section 1](workflow-audit-observability-metrics-and-support.md#1-workflow-audit)) distinguishes:

| Actor Type | Attribution |
|---|---|
| Human user | User Account identity, effective role, active assignment |
| Automation | Automation ID (`AU-XX`), owning role that approved it |
| AI-assisted (advisory only) | Requesting user + AI service identity, per [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 1](../07-ai/ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity) |
| Device-originated | Device Identity + bound human operator |
| Scheduled system process | The scheduler's own service identity, scoped to exactly its declared job |

## 9. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-22 (whether automation service identities require a dedicated credential-rotation cadence distinct from the AI service identity model).
