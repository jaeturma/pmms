# PMMS Medical, Eligibility, Finance, and Sensitive-Data Controls

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [../01-architecture/high-integrity-access-controls.md](../01-architecture/high-integrity-access-controls.md) · [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md)

This document defines governance-level controls for medical, eligibility, and finance data — the three domains carrying the most acute confidentiality/integrity requirements beyond scoring and results (already covered in [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) and [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md)). **No clinical, legal, or accounting standard is defined here.**

---

## Medical Data Governance

| Control | Direction |
|---|---|
| Need-to-know access | Medical detail is visible only to the Medical Team roles whose function requires it, never platform-wide |
| Summary versus detailed records | A minimal clearance-status flag (e.g., "cleared" / "not cleared" / "conditional") is the only medical information exposed to Eligibility and Clearance (BC-09) via the anti-corruption layer, per [../01-architecture/context-map.md, "Anti-Corruption Layers"](../01-architecture/context-map.md#anti-corruption-layers--explicit-justification) — full medical detail never crosses that boundary |
| Emergency access | A candidate elevated-access path for genuine medical emergencies, distinct from routine access, requiring its own reason capture and audit — governed with the same rigor as break-glass access (Section, [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md)) |
| Medical-team scope | Medical role assignments are meet-scoped, per [../01-architecture/scope-model.md](../01-architecture/scope-model.md), never a standing platform-wide grant |
| Export restrictions | Medical-data exports require the elevated authorization and reason capture defined in [data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Download audit | Every medical-record/attachment download is audit-relevant, restated from [audit-and-security-event-architecture.md, Section 4](audit-and-security-event-architecture.md#4-sensitive-data-access-auditing) |
| Offline minimum | Only a minimal emergency-relevant flag (never full medical detail) is ever candidate for offline replication, per [../02-data/offline-sync-and-conflict-data-model.md, Section 1](../02-data/offline-sync-and-conflict-data-model.md#1-offline-replication-data) |
| Attachment control | Medical document attachments follow the full file-upload/malware/classification lifecycle in [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md), classified Highly Restricted |
| Public non-disclosure | Absolute — medical data is never part of any public projection |
| Retention validation | Medical retention is a placeholder pending DepEd/legal/medical-records-authority input, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — not invented here |
| Incident escalation | Any incident involving medical data is escalated with the highest priority tier, per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Segregated reporting | Medical reporting/analytics, if any, is de-identified and aggregated — never individual-level medical reporting outside the direct care-coordination workflow |
| Support-access restrictions | Support/administrative roles do not have standing access to medical detail; any access requires the same elevated governance as Section, [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) |
| AI restrictions | Medical data is excluded from AI-assisted processing entirely unless a future approved DepEd policy explicitly permits a specific, scoped use — restated from [../00-product/open-decisions.md, OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) |
| Break-glass review | Any emergency-access invocation into medical data receives mandatory post-use review, per [authorization-and-privileged-access-assurance.md, Section 6](authorization-and-privileged-access-assurance.md#6-break-glass-access-governance) |

This section governs BC-21 (Medical Operations), blocked pending [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling) for the underlying policy, and SOD-09 for the structural separation from Public Information/Media roles.

## Eligibility Data Governance

| Control | Direction |
|---|---|
| Restricted evidence | Eligibility evidence documents are Restricted-tier, per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) |
| Reviewer and approver access | Distinct roles per SOD-01 — the reviewer/submitter is never the approver for the same case |
| Delegation visibility | A delegation representative sees their own athletes' eligibility status, never another delegation's |
| Athlete visibility | An athlete (or guardian, where access is granted) sees their own eligibility status and outcome, not the full internal review deliberation |
| Guardian visibility | Mirrors athlete visibility, subject to the verified-relationship requirement in [minor-athlete-and-guardian-data-governance.md, Section 2](minor-athlete-and-guardian-data-governance.md#2-guardian-data) |
| Decision transparency | The outcome and its basis (to the extent appropriate) are available to the affected athlete/delegation; the full evidentiary detail behind a decision is not necessarily exposed beyond the review chain |
| Evidence non-disclosure | Eligibility evidence documents are never part of any public projection |
| Public status limits | A public projection may show that an athlete is "eligible to compete" as an operational status; it does not show the underlying evidence or review detail |
| Export restrictions | Per [data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Retention validation | Placeholder, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) |
| Reopen and override auditing | Any reopening or override of an eligibility decision is a distinctly-flagged, elevated-audit event, per [audit-and-security-event-architecture.md, Section 2](audit-and-security-event-architecture.md#2-audit-event-categories) |
| AI review limits | AI may assist in flagging incomplete submissions or summarizing evidence for the human reviewer; it never autonomously approves/rejects an eligibility case, per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |
| Source-policy references | Eligibility rule references cite their DepEd/sports-governing-body source, per [../02-data/test-seed-and-reference-data-strategy.md, Section 1](../02-data/test-seed-and-reference-data-strategy.md#1-reference-and-seed-data-classification) — never an invented eligibility rule |

This section governs BC-09 (Eligibility and Clearance), blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

## Finance Data Governance

| Control | Direction |
|---|---|
| Restricted access | Finance data is Restricted-tier, visible only to Finance Committee roles and relevant oversight |
| Encoder and approver separation | Per SOD-06 — the individual recording an expense/budget allocation is never the same individual approving it |
| Supporting-document controls | Financial supporting documents (receipts, vouchers) follow the same file-upload/classification lifecycle as any other Restricted document |
| Export controls | Per [data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Amount visibility | Individual transaction amounts are visible to Finance/oversight roles; aggregate summaries may be more broadly visible per committee governance, never assumed public |
| Correction history | Financial corrections follow the versioning/supersession discipline in [../02-data/high-integrity-data-model.md, "Finance"](../02-data/high-integrity-data-model.md#finance-bc-26) — never a destructive edit |
| Approval evidence | Every approval action is audit-relevant with actor, reason, and reference, per Section 2 of [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) |
| Vendor-data handling | Vendor/supplier information (where captured) receives the same Restricted-tier treatment as other financial records |
| Retention validation | Placeholder, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — financial records commonly carry longer institutional retention expectations, but no specific period is invented here |
| Audit | Full financial-approval audit trail, restated from [../02-data/high-integrity-data-model.md, "Finance"](../02-data/high-integrity-data-model.md#finance-bc-26) |
| Support restrictions | Support/administrative roles do not have standing access to financial detail beyond what their specific function requires |
| No public disclosure except approved summaries | Only committee-approved, aggregated financial summaries (if any) are ever candidates for public disclosure — raw financial records are never public |

This section governs BC-26 (Finance Operations).

## Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably the medical-emergency-access mechanism's specific design, and whether aggregate financial summaries are ever approved for public disclosure.
