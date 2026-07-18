# PMMS Design, UX, Accessibility, and Cross-Platform Review

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../06-design/phase-0.9-design-system-ux-accessibility-experience.md](../06-design/phase-0.9-design-system-ux-accessibility-experience.md)

---

## 1. Product Surfaces

Nine product surfaces consistently referenced without redefinition through Phase 0.10 (AI UX patterns) and Phase 0.11/0.12 (workflow/enterprise UX touchpoints).

## 2. Navigation

Role/assignment-aware navigation with the absolute rule "hiding an inaccessible destination is a usability convenience, never a security boundary" is the direct precedent for Phase 0.10's AI-authority-visibility and Phase 0.12's tenant-branding-vs-accessibility governance — confirmed consistently reused.

## 3. Accessibility

WCAG treated as a "candidate reference requiring validation," never a compliance claim — restated unchanged through Phase 0.12's explicit "accessibility never relaxed for tenant branding" rule. **No accessibility testing has occurred** — every accessibility claim remains at the Documented level only, consistent with working rule 21 of this review.

## 4. React

Server-authoritative permissions and the confirmed shadcn/ui foundation are consistently the starting point referenced (never re-derived) through every later phase touching frontend concerns.

## 5. Flutter

Consistently scoped, `mobile/` confirmed absent from the repository as of this review — correctly deferred, not a defect.

## 6. Public Portal

"Never publishes provisional, held, restricted, or superseded data" is the architecture's most consistently repeated public-data rule, restated unchanged through Phase 0.10 (public AI boundaries), 0.11 (public workflow boundaries), and 0.12 (CDN/edge-delivery exclusions). **Assessment: Strong.**

## 7. Kiosk and Scanner

Minimal five-step scanner flow and kiosk privacy-timeout discipline (Phase 0.9) are directly extended, not redefined, by Phase 0.11's access-validation workflow and Phase 0.12's device-fleet scaling.

## 8. Scoreboard

Provisional-versus-certified visual distinction (Phase 0.9) is the direct architectural ancestor of Phase 0.11's Reverb provisional-versus-published broadcast rule — confirmed identical principle, consistently reused, never weakened.

## 9. Forms

Draft-is-never-submission discipline (Phase 0.9) is consistently reused in Phase 0.11's workflow-draft distinction without redefinition.

## 10. High-Integrity UX

Every approve/certify/publish/revoke/override action showing authority, scope, state, consequence, reason, evidence, confirmation, audit notice — restated unchanged and never weakened through Phase 0.10 (AI content labeling), 0.11 (human-task UX), and 0.12 (tenant-suspension UX implications, though not yet built).

## 11. User Research Gaps

**All 27 user-group proto-personas explicitly require pilot validation** — none is currently Stakeholder-Validated. This is the design architecture's single most consequential evidence gap, restated absolutely by Phase 0.9 itself and unresolved through Phase 0.12.

## 12. Branding

Institutional branding palette ([DX-02](../06-design/design-open-decisions.md)) remains unapproved, blocking finalization of every brand-carrying visual token — restated unchanged, correctly deferred, not overridden by Phase 0.12's tenant-branding-governance layer (which explicitly builds on, never replaces, the eventual approved palette).

## 13. Recommendation

Design architecture is mature at the Documented/Cross-Validated level. The primary blockers are DX-01 (WCAG target), DX-02 (branding palette), and the unvalidated proto-persona set — all requiring real stakeholder/user engagement, not further documentation.

## 14. Open Questions

DX-01, DX-02, and the sport-specific interface cluster (DX-14/DX-15/DX-16, blocked on OD-10/OD-12/OD-09) remain the highest-priority design decisions — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).
