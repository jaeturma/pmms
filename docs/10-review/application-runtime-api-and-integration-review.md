# PMMS Application, Runtime, API, and Integration Review

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md)

---

## 1. Modular Monolith Direction

Confirmed as the recommended direction since Phase 0.2, given full technical shape in Phase 0.4 (34 modules under `app/Domains/`, Domain/Application/Infrastructure/Delivery layering, Infrastructure→Application→Domain dependency direction), and never revisited or contradicted in any later phase. Explicitly justified against team size, Laravel's native fit, iteration speed given unresolved policy questions, operational simplicity, and extraction-readiness — restated verbatim in Phase 0.2 Section 22. **Assessment: Strong.**

## 2. Laravel Layers

Domain/Application/Infrastructure/Delivery layering with a thin Delivery layer is consistently enforced as an anti-pattern boundary (Phase 0.4 Section 49: "Fat controllers — Prevented by design"). No later phase weakens this. **Assessment: Strong**, not yet Implemented (`app/Domains/` does not exist in the repository — expected for Phase 0).

## 3. React and Inertia

Server-authoritative permissions restated absolutely across Phase 0.4, 0.6, and 0.9 ("frontend hiding is never authorization" — the single most repeated rule in the Phase 0.9 package). Confirmed repository foundation (shadcn/ui "new-york", Tailwind 4, OKLCH tokens, `app-sidebar.tsx`, `use-appearance.tsx`) is consistently treated as the extension point, never replaced. **Assessment: Strong.**

## 4. Flutter

Consistently scoped to offline-critical field operations (Access Validation, Scoring) plus medium-relevance contexts, never assumed to reimplement business rules independently. `mobile/` does not exist — restated unchanged as a finding in every phase since Phase 0.4, correctly not treated as a defect for a documentation-only Phase 0.

## 5. APIs

Six API categories (Internal Mobile, Device, Public, Administrative Integration, Webhook, Synchronization) consistently referenced without redefinition through Phase 0.9 (UX), 0.11 (workflow-triggered webhooks), and 0.12 (enterprise API-product readiness). **Assessment: Strong.**

## 6. Queues, Horizon, Reverb, Redis, MinIO

The 11 queue categories (Phase 0.4) are validated, never redefined, through Phase 0.8 (deployment), 0.11 (workflow reliability), and 0.12 (scaling readiness) — confirmed zero contradiction in this review's consistency analysis. **Assessment: Strong — this is the most consistently cross-referenced runtime boundary in the entire architecture.**

## 7. Scheduler

Consistently described as a singleton, non-horizontally-scaled process across Phase 0.8 and Phase 0.11 — no contradiction found.

## 8. External Integrations

**No external integration is approved anywhere in the 12-phase corpus.** [OD-25](../00-product/open-decisions.md#od-25--integration-requirements) (Integration Requirements) remains open with a recommended direction of "no integrations at launch," restated unchanged through Phase 0.11 (webhook readiness) and Phase 0.12 (integration-marketplace readiness, both explicitly deferred). **Assessment: Strong discipline on a correctly-deferred capability.**

## 9. Overengineering Risks

None found in the Phase 0.4 application/runtime architecture itself. The one area flagged for future vigilance (not a present defect) is the sheer number of conceptual "boundary" documents (18 supporting documents in Phase 0.4 alone) relative to the modest current implementation scope — a documentation-density observation, not an architectural overreach, since none of it commits to premature infrastructure.

## 10. Unclear Boundaries

None found to be materially unclear. The clearest remaining ambiguity is the still-open outbox-versus-`after_commit` dispatch decision ([RD-01](../01-architecture/runtime-open-decisions.md) → [PD-21](../02-data/data-open-decisions.md) → [WD-08](../08-workflows/workflow-open-decisions.md#wd-08--outbox-table-versus-after_commit-dispatch)), now carried unresolved across three consecutive phases — this is the runtime area's single most consequential open decision, since it affects the reliability guarantee for every critical cross-context event (`ResultCertified` → Medal Tally, `AccreditationRevoked` → offline sync).

## 11. Recommendation

The outbox-versus-`after_commit` decision (Section 10) should be resolved via a targeted technical spike early in Phase 0.14, before the first critical cross-context event is implemented — tracked as [GAP-05](architecture-gap-register.md).

## 12. Open Questions

RD-01/PD-21/WD-08 (outbox pattern) and RD-21 (API versioning scheme) remain the highest-priority application/runtime decisions — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).
