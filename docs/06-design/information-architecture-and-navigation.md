# PMMS Information Architecture and Navigation

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) · [../01-architecture/scope-model.md](../01-architecture/scope-model.md) · [product-surfaces-and-workspace-architecture.md](product-surfaces-and-workspace-architecture.md)

This document defines the workspace hierarchy, information architecture, and navigation patterns spanning React and Flutter. **No route, menu component, or navigation code is created here.**

---

## 1. Workspace Hierarchy

```text
Platform
→ Organization
→ Meet
→ Operational Workspace
→ Committee, Delegation, Sport, Venue, or Tournament Context
→ Task or Record
```

At every level of this hierarchy, the user always understands: current organization · current meet · current role or assignment · current scope · current record state · current connectivity state · whether the data they're viewing is Public, Internal, Confidential, Restricted, or provisional. This restates, at the interface layer, the Phase 0.3 scope model's non-inheriting hierarchy — a user's position in the workspace hierarchy is a navigational convenience, never itself a source of authorization (per [../01-architecture/scope-model.md, Section 4](../01-architecture/scope-model.md#4-scope-inheritance-rules)).

## 2. Information Architecture

Twenty-eight primary information domains, each mapped to its owning bounded context (per [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md)) so the interface's structure never drifts from the platform's actual ownership model:

Home and overview · Meets · Organizations · Committees · Delegations · Participants · Registration · Eligibility · Accreditation · Sports · Entries · Tournaments · Schedules · Officials · Scoring · Results · Protests · Medal tally · Venues · Medical · Logistics · Finance · Security · ICT · Media · Reports · Audit · Settings.

**Navigation is role- and assignment-aware without changing terminology unpredictably** — a Meet Administrator and a Technical Official both see "Scoring" labeled identically wherever their respective scopes intersect it; what differs is which specific records and actions each can reach, never the vocabulary itself. This directly extends [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md)'s terminology-governance discipline into the navigation layer.

## 3. Navigation Architecture

### Candidate Patterns

Global application navigation · organization switcher · meet switcher · context navigation · workspace sidebar (already scaffolded in this repository as `app-sidebar.tsx`) · mobile bottom navigation · breadcrumbs (already scaffolded as `breadcrumbs.tsx`) · tabs · command palette · recent items · search · task inbox · notifications.

### Rules

1. **Hide inaccessible destinations for usability, but never rely on hiding for security** — restated absolutely per working rule 25; a navigation item's visibility is a UX convenience, and the underlying route is independently authorization-checked regardless of whether the menu item is shown.
2. **Preserve context during navigation** — moving from a meet's schedule to its entries never silently loses the active meet/organization context.
3. **Clearly indicate current meet and scope** — restated from Section 1.
4. **Avoid deeply nested menus** — a destination more than three levels deep in the navigation tree is a candidate for restructuring, not an acceptable default.
5. **Avoid placing unrelated domains in one generic menu** — restated from the information-architecture domains above; "Settings" is never a catch-all for anything that didn't fit elsewhere.
6. **Support keyboard navigation** — restated from [responsive-touch-keyboard-and-device-behavior.md, Section 3](responsive-touch-keyboard-and-device-behavior.md#3-keyboard-interaction).
7. **Support mobile adaptation** — restated from [responsive-touch-keyboard-and-device-behavior.md, Section 2](responsive-touch-keyboard-and-device-behavior.md#2-mobile-adaptation).
8. **Preserve browser back behavior** — an Inertia page transition never breaks the browser's native back/forward expectation.
9. **Use stable URLs where appropriate** — a meet's schedule page has a bookmarkable, shareable URL reflecting its actual context, not an opaque client-side-only state.

## 4. Page Hierarchy (Conceptual, Not a Screen Inventory)

This document establishes the *structure* pages fit into, not a finalized inventory of every screen — restated per working rule 17 ("Do not finalize a complete screen inventory as if every screen is already approved"). A page's place in the hierarchy is determined by which information domain (Section 2) and which workspace level (Section 1) it belongs to; the specific set of pages within a domain is an implementation-phase detail, informed by but not dictated by this architecture.

## 5. Relationship to Existing Repository Structure

This repository's confirmed navigation scaffolding (`app-sidebar.tsx`, `app-header.tsx`, `nav-main.tsx`, `nav-user.tsx`, `nav-footer.tsx`, `breadcrumbs.tsx`) provides the mechanical foundation — a working sidebar-plus-breadcrumb shell — that this information architecture's twenty-eight domains and role-aware navigation rules are designed to populate. No existing navigation component is modified in this phase, per working rule 15.

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether a command palette is adopted for the administrative portal (a candidate power-user accelerator, not yet committed) and the specific organization/meet-switcher interaction pattern.
