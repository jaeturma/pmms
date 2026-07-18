# PMMS Accreditation, Access-Validation, and Security Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-05, WF-16, WF-21)

This document adds the event, notification, state-machine, and automation layer to the existing Phase 0.2 workflow definitions — it does not redefine WF-05, WF-16, or WF-21's steps, actors, or preconditions.

---

## 1. Accreditation Workflow (WF-05, BC-19, High-Integrity)

Eligibility (precondition) → approval readiness → credential generation → photo-and-identity verification → issuance → print → activation → replacement → revocation → expiry → archive → audit.

- `AccreditationIssued`, `AccreditationRevoked` are the catalog's existing notification-worthy events. `AccreditationRevoked` **must propagate to offline scanners with priority** — restated absolutely from [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md) and [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers)'s Credential-Revocation-to-Access-Denial-Propagation process manager.
- Eligibility-to-Accreditation-Readiness is a named process manager: Accreditation must know Eligibility approval is a precondition, not silently poll for it.
- Credential expiry uses candidate automation AU-01, per [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model) — automation marks a credential Expired only at its already-approved validity end, never revokes a still-valid credential.
- Revocation itself remains a human, high-integrity action, never automated — restated per working rule 43 ("automation must not grant privileges or permanent access") applied symmetrically to revocation.
- SOD-05 applies: the issuer/revoker of credentials must not also unilaterally override scan denials.

## 2. Access Validation Workflow (WF-16, BC-20, High-Integrity, Offline-Critical)

Scan → credential lookup → validation → assignment-and-access-rule check → allow, deny, or warning → offline provisional validation → server reconciliation → incident escalation → audit.

- This is PMMS's highest offline-priority workflow, restated from [../01-architecture/offline-and-synchronization-boundaries.md](../01-architecture/offline-and-synchronization-boundaries.md) — offline scan results are Provisional pending server reconciliation, never treated as final while offline, per [offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 1](offline-mobile-device-public-and-ai-workflow-boundaries.md#1-offline-workflow-boundaries).
- `AccessDenied` is conditionally notification-worthy ("if pattern suggests concern," per the existing catalog) — a single routine denial is not itself alert-worthy; a pattern feeds the Security workflow (Section 3).
- Revocation-list sync is the primary offline-security risk mitigation (RSK-08, per [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)), prioritized above all other reconnection sync content per [../01-architecture/offline-sync-runtime-architecture.md, Section 5](../01-architecture/offline-sync-runtime-architecture.md#5-sync-priority-ordering).
- The five-step QR scanner interaction flow (`Ready → Scan → Processing → Result → Automatic reset`) is defined at the UX layer in [../06-design/accreditation-qr-device-and-shared-station-experience.md, Section 2](../06-design/accreditation-qr-device-and-shared-station-experience.md#2-qr-scanner-experience) — this document adds only the underlying workflow/event architecture, not the interaction design.

## 3. Security Incident Workflow (WF-21, BC-25)

Incident report → classification → assignment → escalation → evidence → containment record → resolution → closure → restricted reporting → audit.

- SOD-10 applies: device provisioning (ICT Coordinator) and security review must be distinct roles.
- A repeated `AccessDenied` pattern (Section 2) or a security event (per [event-taxonomy-ownership-and-contracts.md, Section 1](event-taxonomy-ownership-and-contracts.md#1-event-taxonomy-six-types)) can trigger this workflow's incident-creation step — restated as an event-triggered workflow entry point, never an automated incident *resolution*.
- Restricted reporting: security-incident detail is Confidential/Restricted-tier, never broadcast on a public or low-scope channel, restated from [event-metadata-versioning-ordering-and-correlation.md, Section 6](event-metadata-versioning-ordering-and-correlation.md#6-event-privacy-and-classification).
- Emergency/access-control alerts route to the Administrative channel (Security-scoped), per [realtime-broadcast-and-reverb-message-architecture.md, Section 2](realtime-broadcast-and-reverb-message-architecture.md#2-channel-taxonomy-restated-and-extended).

## 4. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-28 (whether an access-denial-pattern threshold for automatic security-workflow escalation is ever defined, and its numeric threshold).
