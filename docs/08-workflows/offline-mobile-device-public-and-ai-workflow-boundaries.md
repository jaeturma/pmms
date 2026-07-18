# PMMS Offline, Mobile, Device, Public, and AI Workflow Boundaries

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) · [../07-ai/human-in-the-loop-and-authority-model.md](../07-ai/human-in-the-loop-and-authority-model.md)

---

## 1. Offline Workflow Boundaries

Offline clients **may**: create drafts · record provisional field actions · queue approved submissions · display assigned tasks · capture scans · capture incident information.

Offline clients **may not**: certify results · finalize eligibility · resolve protests · publish results · alter permissions · finalize finance approvals.

**All offline actions require server validation and idempotency** — restated absolutely, extending [../01-architecture/offline-sync-runtime-architecture.md, Section 3](../01-architecture/offline-sync-runtime-architecture.md#3-runtime-rules)'s runtime rules with the workflow-specific consequence: an offline-originated command always re-enters a workflow's state machine as a *Pending Sync* / provisional action, never as a directly-finalized transition, per the record-state chain `Local Draft → Pending Sync → Uploaded → Accepted / Rejected / Conflict → Superseded`.

The eight-item never-final-offline list is restated here unchanged and applies to every workflow in this package without exception: **eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, and high-risk overrides.**

## 2. Mobile Workflow Boundaries

Mobile workflows should be: assignment-aware · task-focused · offline-aware · secure · resumable · clear about pending-versus-accepted state · able to recover from rejection and conflict.

`mobile/` does not yet exist in this repository, restated unchanged from every prior phase's identical finding — this section defines boundaries a future Flutter implementation must respect, not a present-tense feature.

## 3. Shared-Device Workflow Boundaries

Restated absolutely from [../06-design/accreditation-qr-device-and-shared-station-experience.md, Section 3](../06-design/accreditation-qr-device-and-shared-station-experience.md#3-shared-device-and-station-experience): every shift change requires individual re-authentication — device trust never substitutes for operator identity (Section, [workflow-identity-authorization-scope-and-separation-of-duties.md, Section 6](workflow-identity-authorization-scope-and-separation-of-duties.md#6-device-trust)). A workflow task queued on a shared device is bound to the authenticated operator at capture time, not to the device itself.

## 4. Public Workflow Boundaries

Public users **may**: view approved public data · submit approved public forms (where one exists and is approved) · request help · receive public announcements.

Public users **may not**: trigger internal workflow transitions · view workflow histories.

**Public users cannot trigger internal workflow transitions** — restated absolutely per working rule 55. A public-facing form submission (if any is ever approved) enters its own bounded, low-authority intake workflow — it never directly invokes a command on an internal, authenticated-only workflow.

## 5. AI Workflow Boundaries

Every AI-assisted workflow step inherits the full Phase 0.10 authority model unchanged — restated absolutely per working rule 35. Specifically:

| Phase 0.10 Rule | Applied to Workflows |
|---|---|
| AI outputs never directly update authoritative state ([../07-ai/human-in-the-loop-and-authority-model.md, Section 1](../07-ai/human-in-the-loop-and-authority-model.md#1-human-in-the-loop-lifecycle)) | An AI-assisted classification, draft, or recommendation feeding a workflow step still requires the workflow's own human-review transition before any state change |
| AI data access is the intersection, never the union ([../07-ai/ai-identity-authorization-scope-and-audit.md, Section 1](../07-ai/ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity)) | An AI-assisted workflow step never grants the AI service identity broader access than the workflow's own human participant already has |
| No AI capability is approved for implementation ([../07-ai/phase-0.10-ai-assisted-platform-architecture.md, Section 36](../07-ai/phase-0.10-ai-assisted-platform-architecture.md#36-capability-specific-architecture-requirements)) | No workflow in this package assumes any of the 13 candidate AI capabilities is active — every AI-touchpoint described here is a future-conditional integration point, not a present capability |
| Anomaly is not accusation; predictive risk is never a sole basis for exclusion or discipline (working rules 32–34, restated in [../07-ai/ai-rules.md](../../.ai/ai-rules.md)) | Directly applicable to any future workflow surfacing an AI-detected anomaly (e.g., a duplicate-athlete candidate feeding the Registration workflow) — the workflow's own human-review step remains the sole disposition authority |

**Any AI-assisted automation must require an authorized deterministic or human confirmation step** — restated absolutely, identical to [responsible-automation-and-authority-boundaries.md, Section 5](responsible-automation-and-authority-boundaries.md#5-ai-assisted-automation-boundaries).

## 6. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-26 (whether any public-facing intake workflow is approved for the initial implementation) and WD-27 (mobile workflow rollout sequencing, mirroring Phase 0.9's DX-18 and Phase 0.10's AX-17).
