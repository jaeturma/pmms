# PMMS Content Design, Terminology, Help, and Onboarding

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md) · [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md)

This document defines content-design standards, terminology governance, help architecture, onboarding, and notification-message content. **No copy is finalized for every page** — restated per working rule 16; this document defines standards and required terminology, not a complete content inventory.

---

## 1. Content Architecture

Content is clear · direct · consistent · respectful · role-aware · actionable · non-technical where possible · specific about state and consequences.

### Avoided Vague Messages

"Something went wrong" · "Invalid input" · "Action failed" — restated absolutely as anti-patterns.

### Preferred Message Structure

What happened · what was preserved · what the user can do · whether support is needed · whether the action can be retried.

## 2. Terminology Governance

Approved domain terms are used consistently, never interchangeably: **validate, approve, certify, publish, revoke, withdraw, return for correction, supersede, reopen, hold, resolve, archive.** Every term above is defined once in [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md) and reused identically across React, Flutter, and every document in this Phase 0.9 package — no synonym is introduced for convenience.

### Distinctions the Interface Must Never Blur

Score and result · validated result and certified result · certified result and published result (restated absolutely from [dashboard-table-chart-and-data-visualization-standards.md, "Certified Versus Published States"](dashboard-table-chart-and-data-visualization-standards.md#certified-versus-published-states)) · credential and user account · role and assignment (restated from [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md)'s Role ≠ Assignment principle) · registration and eligibility · deletion and revocation · correction and supersession (restated from [high-integrity-approval-certification-and-publication-ux.md, Section 4](high-integrity-approval-certification-and-publication-ux.md#4-correction-and-supersession-ux)).

## 3. Date, Time, Score, and Measurement Formatting

Follows [../02-data/database-naming-and-design-standards.md, Sections 4 and 6](../02-data/database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards) exactly — dates/times display in the meet's local time zone (never assumed UTC or the viewer's own time zone without disambiguation), scores/measurements display with the precision and unit their sport-specific rule source specifies (never invented, per working rule 34), and every displayed value that has an authoritative stored form (UTC timestamp, fixed-precision decimal) is derived from, never a re-interpretation of, that stored form.

## 4. Error, Confirmation, and Notification Message Standards

**Error messages** follow Section 1's structure exactly — never a raw exception, never a generic dead-end. **Confirmation messages** state specifically what succeeded, restated from [status-feedback-error-offline-and-sync-patterns.md, Section 9](status-feedback-error-offline-and-sync-patterns.md#9-success-and-destructive-action-confirmation). **Notification messages** (Section 6 below) carry only the minimum necessary content, never a Restricted/Highly Restricted-tier value in a preview.

## 5. Microcopy

Button labels, field hints, and inline guidance follow the same plain-language, action-specific discipline as full messages — a button reads "Certify Result," never a generic "Submit," wherever the specific action has a more precise name available per Section 2's terminology governance.

## 6. Notification Experience

In-app notification center · priority · read state · actionable notifications (a notification links directly to the relevant record/action, never requiring the user to hunt for it) · security notices · deadline notices · assignment notices · result notices · sync notices · system incidents.

### Avoided Patterns

Notification overload (restated from [experience-vision-and-design-principles.md, Section 2](experience-vision-and-design-principles.md#2-ux-principles)) · sensitive information in previews (restated absolutely per working rule 26) · duplicate notifications across channels (a single triggering event produces one coherent notification, not a redundant email+SMS+push+in-app bundle unless the urgency genuinely warrants multi-channel delivery) · notifications without action or context.

## 7. Help Architecture

Contextual help · glossary (linking directly to [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md)) · inline guidance · tooltips · examples · policy or rule references (citing the specific approved source, never an invented rule, per working rule 34) · user guides · committee manuals · searchable help · support contact · status page · error-specific help.

**Avoid excessive tooltips for essential instructions** — restated absolutely; a workflow a user cannot complete without hovering over a tooltip is a design failure the tooltip is papering over, not solving.

## 8. Onboarding

Onboarding is role- and assignment-specific: first login · organization administrator · meet administrator · committee member · tournament manager · technical official · scanner operator · coach or delegation user · public or self-service user. Each onboarding flow surfaces only what that specific role/assignment needs to begin working productively — never a generic, one-size-fits-all product tour.

## 9. Guided Tours, Contextual Help, and Documentation Links

Guided tours (a candidate, optional first-use walkthrough, not a mandatory gate) · contextual help (surfaced at the point of need, per Section 7) · documentation links (connecting in-app help to the fuller reference material, informing [operational-readiness-handover-and-training.md](../05-devops/operational-readiness-handover-and-training.md)'s training model) · training support (per [../04-quality/pilot-operational-and-stakeholder-validation.md, Section 3](../04-quality/pilot-operational-and-stakeholder-validation.md#3-committee-workflow-validation)'s committee-workflow-validation activities, which directly inform what help content is actually needed).

## 10. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether guided tours are built for the initial pilot or deferred, and the specific glossary-to-in-app-help linking mechanism.
