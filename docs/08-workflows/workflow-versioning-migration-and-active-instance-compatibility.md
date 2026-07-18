# PMMS Workflow Versioning, Migration, and Active-Instance Compatibility

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [business-process-and-state-machine-architecture.md](business-process-and-state-machine-architecture.md) · [../05-devops/database-migration-and-release-safety.md](../05-devops/database-migration-and-release-safety.md)

---

## 1. Workflow Definitions and Templates

A workflow definition specifies its states, transitions, timers, permissions, and notifications (per [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture)) for one workflow (e.g., WF-04 Eligibility Validation). A workflow template is a reusable pattern (e.g., a generic "human-approval-with-escalation" shape) a specific workflow definition may be built from — templates are a candidate implementation convenience, not a required architecture element.

## 2. Workflow Versioning

Every workflow definition requires: Workflow ID · version · effective date · status · source rule · states · transitions · timers · permissions · notifications · migration notes · deprecated version · owner.

**Workflow changes must be versioned** — restated absolutely per working rule 51, directly extending [event-metadata-versioning-ordering-and-correlation.md, Section 2](event-metadata-versioning-ordering-and-correlation.md#2-event-schema-versioning)'s event-versioning discipline to workflow definitions themselves.

## 3. Active-Instance Compatibility

**Active workflows must not be silently changed by new definitions** — restated absolutely per working rule 52. When a workflow definition changes:

| Rule | Direction |
|---|---|
| Existing instances | May remain on the prior version until they reach a natural completion or an explicit, reviewed migration |
| New instances | Use the new version from creation |
| Migration | Must be explicit — never an implicit, automatic upgrade of an in-flight instance |
| State mapping | A migration defines exactly how each old-version state maps to a new-version state; an unmappable state blocks migration for that instance pending manual review |
| Pending tasks | Reviewed before migration — a human task valid under the old definition must remain valid, be explicitly reassigned, or be explicitly cancelled, never silently orphaned |
| Timer changes | Reviewed before migration — a timer computed under the old definition's rule is not silently recomputed under a new rule without explicit migration logic |
| Notification compatibility | A notification template referenced by an in-flight instance remains available (or has an explicit fallback) through that instance's completion |
| Audit-history preservation | The original definition version an instance's historical transitions occurred under is always preserved in that instance's audit trail, even after the instance migrates to a newer definition |

This mirrors the phased, backward-compatible pattern already established for database schema changes in [../05-devops/database-migration-and-release-safety.md](../05-devops/database-migration-and-release-safety.md) (add → dual-support code → backfill → validate → switch → remove later) — a workflow-definition change follows the same non-destructive, staged discipline.

## 4. Workflow Configuration

Distinguished: code-defined invariants (never editable outside a code change and review) · policy-driven configuration (e.g., an approved deadline rule, once a policy source exists) · meet configuration (per-meet parameters, e.g., which sports are active) · organization configuration · user preferences (e.g., notification preferences) · feature flags (automation and workflow-capability toggles).

**Core domain rules are never placed in freely editable configuration** — restated absolutely as this section's governing rule. A separation-of-duties requirement, a high-integrity state-transition rule, or an authorization boundary is a code-defined invariant, never a runtime-configurable setting an administrator could silently loosen.

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-25 (whether a dedicated workflow-template mechanism is ever built, versus each workflow definition remaining hand-specified).
