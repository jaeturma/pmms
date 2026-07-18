# ADR-0009: Design System, UX, Accessibility, and Cross-Platform Experience Architecture

## Status

Accepted (as a Phase 0.9 design-architecture decision; pending formal product, UX, accessibility, domain, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0008 established PMMS's bounded contexts, authorization model, runtime architecture, data/persistence architecture, security/privacy/audit/governance architecture, quality-engineering architecture, and DevOps/operations architecture. None of them specified how a human actually experiences the platform — how a tournament manager reconfigures a bracket under deadline pressure, how a guardian checks a result on a low-bandwidth connection, how a scanner operator validates credentials in direct sunlight, or how an interface visually communicates that a result is provisional rather than certified.

Left unspecified, this gap risks the same failure mode every prior phase's centralization work was built to prevent, now expressed at the interface layer: 28 information domains across 9 product surfaces implemented independently would invent terminology, state visualization, and interaction patterns per screen — producing an interface that, however technically correct underneath, confuses users about what they're looking at and undermines the institutional trust the preceding eight phases spent building. A second, PMMS-specific risk this ADR addresses directly: this repository already has a working, tested shadcn/ui and Tailwind CSS 4 foundation (`components.json`, `resources/css/app.css`, `use-appearance.tsx`) — a design-system effort that ignored this confirmed foundation and proposed a parallel or replacement system would discard real, functional work for no benefit.

## Decision

PMMS will use a **cross-platform design system — named PMMS Arena — built as a direct extension of this repository's confirmed shadcn/ui and Tailwind 4 foundation, governed by explicit state-visibility, privacy, and terminology discipline, and validated against real users before being treated as authoritative.**

Specifically:

1. **The design system extends, never replaces, the confirmed existing foundation.** The shadcn/ui "new-york" style, Tailwind 4, and the existing OKLCH token structure in `resources/css/app.css` remain the foundation; six new PMMS-specific semantic state tokens (provisional, certified, published, held, offline, conflict) are added on top of it to satisfy working rule 33's explicit-state-visibility requirement.
2. **Frontend hiding is never authorization.** Restated absolutely from every prior phase's identical principle, now made the single most repeated rule in this Phase 0.9 package — every component that hides based on role/permission/classification does so for usability only; the server independently enforces the actual restriction on every request.
3. **High-integrity states are always visually distinct, and high-integrity actions are never conflated.** Draft, Provisional, Validated, Certified, Held, Superseded, and Published never share the same visual treatment. Review, recommend, approve, certify, publish, and override are five distinct, never-conflated interface actions, each requiring visible authority, scope, consequence, reason, and audit notice.
4. **Public surfaces never display provisional, held, restricted, or superseded data.** The public portal, kiosk, and scoreboard read only approved, certified, published projections — restated absolutely per working rule 36, directly reflecting the Phase 0.5 public-projection model.
5. **Minor-athlete, medical, eligibility, and financial data receive interface-layer protection matching their Phase 0.6 classification.** Restricted public profiles, controlled photo publication (never automatic), the strongest privacy boundary for medical data, and encoder/approver interface separation for finance are all restated absolutely, never weakened at the interface layer.
6. **AI-assisted interface elements never present themselves as the final decision-maker** for eligibility, scores, results, protests, medals, medical decisions, permissions, credential revocation, or financial approvals — restated absolutely and without exception from ADR-0006/0008's AI-boundary discipline, now given explicit interface expression (labeling, source references, confidence indication, mandatory human confirmation through the ordinary authorized workflow).
7. **React and Flutter share semantics, not pixels.** Terminology, semantic colors, status meanings, and offline-state vocabulary are identical across both platforms; navigation, layout density, touch patterns, and native platform conventions may legitimately differ.
8. **No sport-specific rule is ever invented in an interface.** Bracket formats, scoring precision, ranking rules, and protest deadlines are all marked as requiring validation against an approved DepEd/sports-governing-body source before being treated as final.
9. **Twenty-seven user groups are documented as proto-personas explicitly requiring pilot validation** — never treated as researched, authoritative personas until confirmed against real committee staff, technical officials, and athletes/guardians.

**Explicitly not decided by this ADR:** the final institutional branding palette, the specific WCAG conformance level, final responsive breakpoint pixel values, browser/Android version support baseline, charting-library selection, Storybook/component-preview tooling adoption, and every sport-specific interface detail still blocked on Phase 0.1's unresolved eligibility/protest/medal-tally/sports-rule-source decisions.

## Rationale

- **Preserves every prior ADR's guarantees at the layer users actually see.** An authorization model, a versioned data model, an audit architecture, and a risk-based test strategy are only as trustworthy as the interface that presents them — this ADR is where those guarantees become visible and comprehensible to an actual person under actual operating conditions.
- **Avoids discarding a working, tested foundation.** This repository already has a functional shadcn/ui + Tailwind 4 + OKLCH-token implementation with working light/dark theming — building PMMS Arena as an extension of it, rather than a replacement, preserves real engineering investment and reduces implementation risk.
- **Protects the platform's highest-stakes data at the exact point a careless interface decision could undermine it.** A result that looks the same whether it's provisional or certified, or a public page that accidentally surfaces a held result, is not a cosmetic bug — it directly contradicts the institutional-trust guarantee every prior phase was built to establish.
- **Matches PMMS's actual operating conditions, not a generic enterprise-software template.** Field conditions, intermittent connectivity, shared devices, and minor-athlete data sensitivity are treated as core design constraints (per working rules 37–40) because they are PMMS's actual operating reality, not edge cases to harden against afterward.
- **Avoids both premature visual commitment and premature vagueness.** No branding palette, no font, no logo, and no final breakpoint is finalized without evidence — but the token architecture, component taxonomy, terminology governance, and accessibility requirements every future visual decision must respect are fully decided, so Phase 0.10 begins from a specified foundation rather than a blank slate.

## Approved Experience Architecture Direction

> Build PMMS Arena as a direct extension of the confirmed shadcn/ui and Tailwind 4 foundation already in this repository, with explicit state-visibility for every high-integrity workflow, absolute frontend-hiding-is-never-authorization discipline, privacy-appropriate treatment of minor/medical/eligibility/financial data, an unambiguous AI-boundary, and shared cross-platform semantics between React and Flutter — validating every proto-persona and every sport-specific interface detail against real users and approved rule sources before treating either as final.

## Frontend-Authorization Rule (New in This Phase, Extending ADR-0003/0006)

No interface element — a hidden menu item, a disabled button, a masked field — is ever treated as the actual security boundary. Every sensitive action is independently, server-side enforced regardless of what the UI shows or hides, restated absolutely from working rule 25.

## High-Integrity State-Visibility Rule (New in This Phase, Extending ADR-0005/ADR-0006)

Every high-integrity action (approve, certify, publish, revoke, correct, supersede) displays its actor's authority, scope, current state, consequence, reason, evidence, and resulting state before proceeding — restated absolutely from working rule 33, now given full interface specification in [../../docs/06-design/high-integrity-approval-certification-and-publication-ux.md](../../docs/06-design/high-integrity-approval-certification-and-publication-ux.md).

## AI-Boundary Rule (New in This Phase, Extending ADR-0006/ADR-0008)

An AI-assisted interface element is always labeled as such, always shows its source and limitations, and never substitutes for the human confirmation the ordinary authorized workflow requires for eligibility, scores, results, protests, medals, medical decisions, permissions, credential revocation, or financial approvals.

## Consequences

**Positive:**
- Phase 0.10 inherits a complete design-token architecture, component taxonomy, terminology governance, and accessibility requirement set built directly on this repository's confirmed, working foundation, and can begin component implementation against known, consistent expectations rather than inventing conventions per screen.
- High-integrity workflows have their state-visibility and action-distinction requirements named before any component exists to blur them.
- The platform's highest-sensitivity data categories (minor-athlete, medical, eligibility, financial) have explicit, differentiated interface treatment defined before implementation could accidentally under-protect them.

**Negative / trade-offs:**
- Six new PMMS-specific semantic state tokens add real token-count and mental-model complexity beyond a standard shadcn implementation — accepted because the alternative (indistinguishable provisional/certified/published states) directly undermines working rule 33's core requirement.
- Deferring branding-palette, WCAG-target, and breakpoint finalization preserves flexibility but means several visual decisions cannot be locked until DepEd stakeholder input and real-device/pilot evidence arrive — an accepted sequencing cost, consistent with never finalizing without evidence.
- A significant number of decisions remain open (24 items in [../../docs/06-design/design-open-decisions.md](../../docs/06-design/design-open-decisions.md)), with the sport-specific-detail cluster (DX-14/DX-15/DX-16) blocked on the same Phase 0.1 policy questions that have constrained every prior phase.

## Alternatives Considered

1. **Adopt a third-party enterprise design system wholesale instead of building on the existing shadcn/Tailwind foundation.** Rejected — this repository already has a working, tested foundation; replacing it would discard confirmed functional work for no demonstrated benefit.
2. **Begin page implementation directly from the starter kit without a dedicated experience-architecture phase.** Rejected — would repeat the exact per-screen inconsistency risk every prior phase's centralization effort exists to prevent, now at the interface layer specifically.
3. **Require pixel-identical components between React and Flutter.** Rejected — restated absolutely per the phase's own working instruction; platform-appropriate divergence is a feature when shared semantics (terminology, color meaning, state vocabulary) hold, not a defect.
4. **Finalize a complete screen inventory now to give implementation teams definitive scope.** Rejected — directly violates working rule 17; the information architecture and component taxonomy provide a structure to build within without prematurely locking every specific screen ahead of real requirements and pilot feedback.
5. **Claim WCAG AA compliance now to reassure stakeholders early.** Rejected — directly violates the "no compliance claimed until tested" discipline restated from ADR-0006; AA is a recommended target, not an achieved status.
6. **Let AI-assisted interface elements auto-apply their suggestions for low-risk-seeming cases (e.g., duplicate-match merges) without human confirmation.** Rejected — no exception is carved out from the absolute AI consequential-action boundary; every AI suggestion, regardless of perceived risk, flows through the same human-confirmed, ordinary authorized workflow.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated UX lead, design-system owner, accessibility specialist, content-design lead, React/Flutter design-system engineers, domain reviewers, and DepEd Leadership, per [../../docs/06-design/README.md, "Ownership and Review Expectations"](../../docs/06-design/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.9 open decisions, per [../../docs/06-design/design-open-decisions.md, "Summary of Blocking / High-Priority Design Decisions"](../../docs/06-design/design-open-decisions.md#summary-of-blocking--high-priority-design-decisions) — notably DX-02 (branding-palette approval) and DX-01 (WCAG conformance target).
- Continued resolution of the Phase 0.1 policy decisions this ADR's sport-specific interface rules depend on (eligibility authority, protest and appeal authority, medal tally rules, sports rule source, AI-service restrictions).
- Pilot-based validation of every proto-persona in [../../docs/06-design/user-groups-personas-and-contexts.md](../../docs/06-design/user-groups-personas-and-contexts.md) before being treated as design-authoritative.

## Related Documents

- [../../docs/06-design/phase-0.9-design-system-ux-accessibility-experience.md](../../docs/06-design/phase-0.9-design-system-ux-accessibility-experience.md)
- [../../docs/06-design/cross-platform-design-system-architecture.md](../../docs/06-design/cross-platform-design-system-architecture.md)
- [../../docs/06-design/design-tokens-and-visual-language.md](../../docs/06-design/design-tokens-and-visual-language.md)
- [../../docs/06-design/high-integrity-approval-certification-and-publication-ux.md](../../docs/06-design/high-integrity-approval-certification-and-publication-ux.md)
- [../../docs/06-design/privacy-security-and-sensitive-data-experience.md](../../docs/06-design/privacy-security-and-sensitive-data-experience.md)
- [../../docs/06-design/ai-assisted-experience-architecture.md](../../docs/06-design/ai-assisted-experience-architecture.md)
- [../../docs/06-design/accessibility-architecture.md](../../docs/06-design/accessibility-architecture.md)
- [../../docs/06-design/design-open-decisions.md](../../docs/06-design/design-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../design-system-rules.md](../design-system-rules.md)
- [../ux-rules.md](../ux-rules.md)
- [../accessibility-rules.md](../accessibility-rules.md)
- [../content-design-rules.md](../content-design-rules.md)
- [../data-visualization-rules.md](../data-visualization-rules.md)
- [../mobile-experience-rules.md](../mobile-experience-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
- [ADR-0006-security-privacy-audit-compliance-and-data-governance.md](ADR-0006-security-privacy-audit-compliance-and-data-governance.md)
- [ADR-0007-quality-engineering-testing-validation-and-assurance.md](ADR-0007-quality-engineering-testing-validation-and-assurance.md)
- [ADR-0008-devops-environment-cicd-deployment-and-operations.md](ADR-0008-devops-environment-cicd-deployment-and-operations.md)
