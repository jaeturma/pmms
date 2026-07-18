# ADR-0011: Event-Driven Workflow, Notification, Messaging, and Responsible Automation Architecture

## Status

Accepted (as a Phase 0.11 workflow-architecture decision; pending formal domain, workflow, security, quality, operations, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0010 established PMMS's bounded contexts, authorization model, application/event/queue runtime, data/persistence architecture, security/privacy/audit/governance architecture, quality-engineering architecture, DevOps/operations architecture, design/UX architecture, and AI-assisted platform architecture. Phase 0.2's [workflow-and-command-catalog.md](../../docs/01-architecture/workflow-and-command-catalog.md) and [domain-events-catalog.md](../../docs/01-architecture/domain-events-catalog.md) named 23 workflows and 30+ domain events; Phase 0.4's [event-and-queue-architecture.md](../../docs/01-architecture/event-and-queue-architecture.md), [notification-architecture.md](../../docs/01-architecture/notification-architecture.md), and [realtime-architecture.md](../../docs/01-architecture/realtime-architecture.md) defined the queue categories, notification channels, and Reverb channel taxonomy. None of them, however, specified how a real business process actually coordinates end to end: what durable state a multi-week registration workflow keeps between steps, what explicit process manager reconciles a certified result with a recalculated medal tally, what authority boundary a scheduled credential-expiry automation operates within, or what versioning discipline lets a workflow definition change without silently breaking every instance already in flight.

Left unspecified, this gap risks the same failure mode every prior phase's centralization work was built to prevent, now expressed at the process-coordination layer specifically: 25 named workflows, each implemented independently, would each invent its own state vocabulary, retry behavior, notification pattern, and automation shortcut — reproducing exactly the fragmentation the absolute high-integrity boundaries in every prior ADR exist to prevent, now happening in the connective tissue between them rather than within any single bounded context.

## Decision

PMMS will coordinate every business process through **explicit process managers and state machines, a versioned event-contract and message-reliability discipline, a notification lifecycle that is never itself workflow state, and a Responsible Automation Authority Model that bounds every automated action to an explicit, human-approved, auditable, and disablable authority — extending, never redefining, the existing WF-01–WF-23 workflow catalog and domain-events catalog.**

Specifically:

1. **This phase extends, and never redefines, the existing Phase 0.2 catalogs.** The 23-workflow catalog and the domain-events catalog remain unchanged; this ADR adds the state-machine, process-manager, event-contract, notification, and automation layer on top, with two new workflows (WF-24 Finance, WF-25 ICT) continuing the existing numbering rather than starting a parallel scheme.
2. **MySQL remains authoritative for workflow and business state; Redis and Reverb messages remain transient.** Restated absolutely from working rules 22–25 — a live scoreboard tick, a queued job payload, and a delivered notification are never the record of truth; the owning context's own durable state always is.
3. **Six named process managers coordinate every identified cross-context workflow explicitly.** Eligibility-to-Accreditation Readiness, Certified-Result-to-Medal-Tally Recalculation, Protest-Filing-to-Result-Hold, Credential-Revocation-to-Access-Denial Propagation, Meet-Closure-to-Assignment-and-Credential-Expiration, and Registration-Acceptance-to-Competition-Entry-Readiness — each with documented durable state, never an unlabeled chain of event listeners.
4. **Every retryable action is idempotent, and every consumer tolerates delayed or duplicate messages.** No global event ordering is assumed. The outbox-versus-`after_commit` reliability decision, carried unresolved from Phase 0.4 through Phase 0.5, remains explicitly deferred rather than arbitrarily resolved without real implementation evidence.
5. **Notification delivery is never workflow state and never proof of understanding.** Restated absolutely per working rule 45 — a notification is a courtesy copy of an already-durably-recorded decision; its failure never blocks or reverses that decision, and mandatory notices (security, privilege, credential, emergency) can never be suppressed by user preference.
6. **Automation operates within an absolute, non-negotiable authority boundary.** Automation must never approve eligibility, alter or certify official scores or results, resolve protests, award medals outside approved deterministic workflows, make medical decisions, approve financial actions, grant privileges or permanent access, or publish protected information — restated absolutely per working rules 37–44. Every automation entry requires an explicit owner, purpose, feature flag, audit trail, failure behavior, and disablement path (working rule 50); none of the six illustrative candidate entries in this package is enabled.
7. **Any AI-assisted automation requires an authorized deterministic or human confirmation step.** Restated absolutely, extending Phase 0.10's advisory-only AI boundary into the automation layer specifically — an AI recommendation never becomes an automation's trigger condition by itself.
8. **The eight-item never-final-offline list applies unchanged to every workflow in this package.** Eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, and high-risk overrides — restated absolutely, carried unmodified from every prior phase's offline-authorization discipline.
9. **Active workflow instances are never silently changed by a new definition.** Every workflow-definition change requires explicit migration, state mapping, pending-task review, timer review, and preserved audit history for the original definition version an instance's historical transitions occurred under.

**Explicitly not decided by this ADR:** the outbox-table-versus-`after_commit` dispatch mechanism, event/notification retention numeric values, workflow-metric SLA targets, maximum-retry/backoff numeric values, mail/SMS/push-provider vendor selection, which automation entry is piloted first, and every other item tracked in [../../docs/08-workflows/workflow-open-decisions.md](../../docs/08-workflows/workflow-open-decisions.md).

## Rationale

- **Preserves every prior ADR's high-integrity boundary by finally specifying the coordination mechanism that connects them.** ADR-0004's synchronous/asynchronous discipline, ADR-0003's separation-of-duties matrix, and ADR-0006's audit architecture are only as trustworthy as the workflow layer that sequences the actions they govern — this ADR is where that sequencing becomes an explicit, versioned architecture rather than an implicit assumption each future workflow implementation would have reinvented independently.
- **Prevents 25 workflow implementations from each inventing incompatible retry, notification, and automation conventions.** A shared state-machine discipline, a shared Automation Authority Model, and a shared notification lifecycle mean every future workflow inherits the same guardrails rather than reproving them inconsistently, one workflow at a time.
- **Protects PMMS's highest-stakes processes at the exact point an automated shortcut could quietly erode human accountability.** The automation authority boundary exists because a scheduled task or event-triggered automation is a strictly more dangerous place to place a business-approval shortcut than a human-facing form, given how invisible an unreviewed automated decision can become without the audit and feature-flag discipline this ADR requires.
- **Avoids both premature workflow-engine investment and premature vagueness.** No generic workflow-engine package, saga framework, or outbox implementation is adopted — but the process-manager boundaries, state-machine discipline, and versioning/migration requirements every future implementation must respect are fully specified, so the next phase to actually build a workflow begins from a governed foundation rather than a blank slate.
- **Matches PMMS's actual process complexity, not a generic BPM-product template.** 25 well-bounded, mostly single-context workflows with six identified cross-context coordination points do not justify a general-purpose workflow-authoring engine — hand-rolled orchestration, evaluated explicitly against saga-readiness criteria, is the architecturally honest starting point.

## Approved Workflow Architecture Direction

> Extend the existing WF-01–WF-23 workflow catalog and domain-events catalog with explicit state machines, six named process managers, a versioned event-contract and idempotent-message-reliability discipline, a notification lifecycle that never substitutes for a decision's own durable record, and a Responsible Automation Authority Model that bounds every automated action within an explicit, auditable, disablable authority — activating no workflow, event, notification, or automation implementation in this phase, and approving no automation entry for production use.

## Automation-Authority Rule (New in This Phase, Extending ADR-0006/0010)

No automated action — scheduled, event-triggered, or AI-assisted — ever approves eligibility, certifies results, resolves protests, awards medals outside deterministic calculation, makes medical decisions, approves finances, grants privileges, or publishes protected data, restated absolutely per working rules 37–44. What is new in this phase is the specific mechanism enforcing it: the Automation Authority Model's per-entry field structure, the feature-flag-default-Off requirement, and the mandatory human-confirmation gate for any AI-assisted automation.

## Durable-State-Over-Transient-Transport Rule (New in This Phase, Extending ADR-0004)

MySQL is authoritative for every workflow's business state; Redis, queue payloads, and Reverb broadcasts are transport and cache only — restated absolutely, with the specific consequence that a notification's delivery status, a broadcast's arrival, or a queue job's completion are never themselves treated as the record of a business decision.

## Consequences

**Positive:**
- Any future phase that implements a workflow, event handler, notification, or automation inherits a complete state-machine, process-manager, message-reliability, and Automation Authority Model architecture, and can begin implementation against known, consistent expectations rather than inventing coordination conventions per workflow.
- The platform's highest-stakes cross-context coordination points (certified result → medal tally, credential revocation → offline access denial, protest filing → result hold) have their reliability requirements named and evaluated before any implementation could accidentally under-protect them.
- Automation's absolute authority boundary is specified before any automation exists to violate it, closing the gap between "AI/automation never makes a high-integrity decision" as a repeated principle and as an actually-enforced architecture.

**Negative / trade-offs:**
- No workflow, notification, or automation is usable at the end of this phase — deliberately, but this means the coordination benefits this package's own vision statement describes remain entirely unrealized until a future phase completes at least the first pilot workflow's implementation.
- A significant number of decisions remain open (32 items in [../../docs/08-workflows/workflow-open-decisions.md](../../docs/08-workflows/workflow-open-decisions.md)), including the outbox-versus-`after_commit` decision now carried unresolved across three consecutive phases (Phase 0.4's RD-01, Phase 0.5's PD-21, now WD-08).
- The Automation Authority Model's per-entry review requirement adds real governance overhead beyond simply writing a scheduled command — accepted because the alternative (an ungoverned scheduled task quietly acquiring business-decision authority over time) is exactly the risk this ADR exists to prevent.

## Alternatives Considered

1. **Redefine WF-01 through WF-23 from scratch with this package's vocabulary baked in.** Rejected — directly violates working rule 4; this ADR extends the existing catalog, it does not replace it.
2. **Adopt a generic workflow-engine or BPM package immediately.** Rejected — directly violates working rule 16; PMMS's 25 well-bounded workflows do not justify the complexity of a general-purpose workflow-authoring engine at this stage.
3. **Allow each workflow implementation to define its own retry, idempotency, and notification conventions independently.** Rejected — would reproduce exactly the fragmentation this ADR exists to prevent, with no consistent reliability or audit guarantee across workflows.
4. **Let low-risk-seeming automation (e.g., an obvious duplicate-athlete merge) auto-apply an AI recommendation without human confirmation.** Rejected — no exception is carved out from the absolute AI-assisted-automation boundary; every AI-assisted automation requires a deterministic or human confirmation step regardless of perceived risk.
5. **Resolve the outbox-versus-`after_commit` question now to unblock reliability guarantees.** Rejected — resolving it without real implementation evidence would be an invented answer, not an evaluated one; the decision remains explicitly deferred, consistent with every prior phase's "no invented numbers/decisions without evidence" discipline.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated workflow governance owner, Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Operations owner, and DepEd Leadership, per [../../docs/08-workflows/README.md, "Ownership and Review Expectations"](../../docs/08-workflows/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.11 open decisions, per [../../docs/08-workflows/workflow-open-decisions.md, "Summary of Blocking / High-Priority Workflow Decisions"](../../docs/08-workflows/workflow-open-decisions.md#summary-of-blocking--high-priority-workflow-decisions) — notably WD-12 (eligibility/protest/appeal deadlines, blocking on OD-07/OD-09) and WD-08 (outbox timing, unresolved since Phase 0.4).
- Continued resolution of the Phase 0.1 policy decisions several domain-specific workflow documents depend on (OD-07 eligibility authority, OD-09 protest and appeal authority, OD-10 sports rule source, OD-12 medal tally rules, OD-15 medical-data handling).
- A completed structural-versus-audit-detectable enforcement review for every separation-of-duties rule named across the six domain-specific workflow documents, before any workflow reaches implementation.

## Related Documents

- [../../docs/08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md](../../docs/08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md)
- [../../docs/08-workflows/workflow-vision-principles-and-governance.md](../../docs/08-workflows/workflow-vision-principles-and-governance.md)
- [../../docs/08-workflows/business-process-and-state-machine-architecture.md](../../docs/08-workflows/business-process-and-state-machine-architecture.md)
- [../../docs/08-workflows/orchestration-choreography-and-process-manager-architecture.md](../../docs/08-workflows/orchestration-choreography-and-process-manager-architecture.md)
- [../../docs/08-workflows/outbox-inbox-idempotency-and-message-reliability.md](../../docs/08-workflows/outbox-inbox-idempotency-and-message-reliability.md)
- [../../docs/08-workflows/notification-and-recipient-resolution-architecture.md](../../docs/08-workflows/notification-and-recipient-resolution-architecture.md)
- [../../docs/08-workflows/responsible-automation-and-authority-boundaries.md](../../docs/08-workflows/responsible-automation-and-authority-boundaries.md)
- [../../docs/08-workflows/workflow-incident-change-and-release-governance.md](../../docs/08-workflows/workflow-incident-change-and-release-governance.md)
- [../../docs/08-workflows/workflow-open-decisions.md](../../docs/08-workflows/workflow-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../workflow-rules.md](../workflow-rules.md)
- [../event-rules.md](../event-rules.md)
- [../queue-rules.md](../queue-rules.md)
- [../notification-rules.md](../notification-rules.md)
- [../messaging-rules.md](../messaging-rules.md)
- [../automation-rules.md](../automation-rules.md)
- [../scheduler-rules.md](../scheduler-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
- [ADR-0006-security-privacy-audit-compliance-and-data-governance.md](ADR-0006-security-privacy-audit-compliance-and-data-governance.md)
- [ADR-0007-quality-engineering-testing-validation-and-assurance.md](ADR-0007-quality-engineering-testing-validation-and-assurance.md)
- [ADR-0008-devops-environment-cicd-deployment-and-operations.md](ADR-0008-devops-environment-cicd-deployment-and-operations.md)
- [ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md](ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md)
- [ADR-0010-ai-assisted-platform-architecture.md](ADR-0010-ai-assisted-platform-architecture.md)
