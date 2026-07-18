# PMMS High-Integrity Approval, Certification, and Publication UX

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [../01-architecture/high-integrity-access-controls.md](../01-architecture/high-integrity-access-controls.md) · [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md)

This document defines the interface patterns for eligibility approval, result certification, protest resolution, medal tally certification, and every other high-integrity action named across Phases 0.2–0.7. **No component or workflow implementation is created here.**

---

## 1. High-Integrity Action Experience

For actions such as approve eligibility · certify result · resolve protest · certify medal tally · revoke credential · approve financial report · use emergency access — every interface requires visible: user authority (what role/assignment grants this action) · scope (which meet/sport/committee it applies to) · current state (what state the record is in right now) · consequence (what will change if this action proceeds) · reason (captured where the action requires it) · evidence or source · confirmation (a deliberate, non-accidental step) · audit notice (the action will be recorded) · resulting state (what state the record moves to) · next responsible role (who, if anyone, acts next).

This directly implements working rule 33's requirement that official score entry, result certification, eligibility decisions, and medal tallying never proceed without explicit state and audit visibility.

## 2. Approval and Certification Interfaces

**Approval interfaces distinguish review, recommend, approve, certify, publish, and override as separate, never-conflated actions** — restated absolutely; no generic single "Approve" button covers more than one of these distinct authorities, directly extending [content-design-terminology-help-and-onboarding.md, Section 2](content-design-terminology-help-and-onboarding.md#2-terminology-governance) into interface design.

Every approval/certification screen shows: decision summary · evidence · outstanding issues · separation-of-duties warning (if the current user's involvement in a prior step would create a SoD conflict, per [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md), the interface blocks the action, not merely warns) · conflict declaration · reason · timestamp · digital-acknowledgment readiness (a candidate future e-signature-style mechanism, not committed to a specific implementation).

## 3. Audit-History and Change-History Interfaces

Every high-integrity record exposes its full history to authorized viewers: actor, action, before/after state, reason, and timestamp for every recorded transition, per [../03-security/audit-and-security-event-architecture.md, Section 1](../03-security/audit-and-security-event-architecture.md#1-audit-event-conceptual-fields). This is presented as a **chronological, append-only timeline view**, never a single "last modified by" field that discards earlier history — directly reflecting the append-only data model from [../02-data/temporal-history-and-versioning-model.md, Section 3](../02-data/temporal-history-and-versioning-model.md#3-immutable-history-and-append-only-records).

## 4. Correction and Supersession UX

**No silent editing of a finalized record is ever presented as possible** — restated absolutely. The interface instead offers: a correction request flow · reason capture · a side-by-side view of the original version and the proposed correction · a list of impacted downstream records (e.g., correcting a score after tally certification flags the tally for re-verification) · required revalidation steps · the resulting superseded/new version pair · publication impact (if the corrected record was already public) · notification of affected parties · full audit history — restated from [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture).

## 5. Publication UX

Before publication, the interface shows: source state (what certified record this publication reflects) · certification status · privacy filtering (which fields the public version will actually contain, per [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md)) · public fields (an explicit preview, not an assumption) · publication audience · publication time · expiry if applicable · existing published version (if one is being replaced) · cache or projection update status.

Controlled actions available: publish · unpublish · correct · supersede · schedule publication. Every one of these is a distinct, explicitly-labeled action — restated from Section 2's terminology discipline.

## 6. Protest and Appeal UX

Filing · evidence upload · deadline placeholder (no specific deadline invented, per working rule 34) · affected result (clearly linked) · result hold (visibly indicated on the affected result while a protest is pending, per [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md)) · review status · conflict declaration (a reviewer with a SoD conflict is blocked, restated from Section 2) · decision · appeal · public status (a protest's existence and outcome are shown publicly only to the extent the eventual protest-authority decision (per [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)) permits) · resolution history.

**No official deadline or authority is invented** — restated absolutely per working rule 34; every deadline/authority reference in a protest interface is explicitly marked "pending policy source" until [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) resolves.

## 7. Relationship to Prior-Phase High-Integrity Rules

This document adds interface expression to controls already established: [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) (Phase 0.2), [../01-architecture/high-integrity-access-controls.md](../01-architecture/high-integrity-access-controls.md) (Phase 0.3), [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) (Phase 0.5), and [../03-security/authorization-and-privileged-access-assurance.md](../03-security/authorization-and-privileged-access-assurance.md) (Phase 0.6) — none of these is redefined here, only made visible and actionable in the interface.

## 8. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether a digital-acknowledgment/e-signature mechanism is adopted for certification actions and the specific SoD-conflict-blocking interface treatment (hard block vs. block-with-override-audit).
