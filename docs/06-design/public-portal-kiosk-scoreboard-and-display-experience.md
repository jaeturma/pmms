# PMMS Public Portal, Kiosk, Scoreboard, and Display Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) · [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary)

This document defines the Public Portal, Kiosk, Public Scoreboard/Venue Display, and Report/Print experiences. **No page, screen, or print stylesheet is created here.**

---

## 1. Public Portal Experience

Prioritizes: fast load on low-bandwidth connections (restated from [flutter-mobile-experience-architecture.md, Section 4](flutter-mobile-experience-architecture.md#4-low-bandwidth-experience), applied equally to the web public portal) · mobile-friendly by default, not a desktop-first design retrofitted smaller · no login required for public content · clear navigation to schedules/results/medal tally/announcements · privacy-filtered content exclusively, per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections) · accessible to the same standard as the administrative portal, per [accessibility-architecture.md](accessibility-architecture.md) — public users are never treated as a lower accessibility priority.

**Never publish provisional, held, restricted, or superseded data in public surfaces** — restated absolutely per working rule 36; the public portal reads only approved, certified, published projections, exactly as [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) and [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) already establish at the data and workflow layers.

## 2. Kiosk Experience

Large touch targets · minimal navigation (a small, fixed set of destinations: schedule, results, venue directions, announcements, help) · session reset (returns to a neutral start state after a period of inactivity) · privacy timeout (restated below) · no protected data persistence (a kiosk never retains what a prior user viewed) · public-only or verified self-service data (a kiosk never exposes Confidential-or-above data even to an authenticated self-service session, unless a specific, narrowly-scoped verified-identity flow is deliberately designed) · search · schedule · venue directions · public results · announcements · help · accessibility (per [accessibility-architecture.md, Section 6](accessibility-architecture.md#6-kiosk-and-scoreboard-accessibility)) · language readiness (per [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md)) · offline content fallback (a kiosk with lost connectivity degrades to a cached, clearly-labeled "may not be current" state rather than a broken page).

**Shared-device and kiosk experiences must minimize privacy exposure** — restated absolutely per working rule 40; the session-reset and no-persistence rules above exist specifically to satisfy this.

## 3. Public Scoreboard and Venue Display (Scoreboard Standards)

Scoreboards prioritize: distance readability · high contrast · large numeric display · clear competitor identification · match or event status · period/set/round/heat/lane context as applicable to the sport · timing · result state (explicitly distinguishing provisional from certified, restated absolutely per working rule 33's spirit extended to public display) · sponsor or branding restraint (institutional branding never crowds out the actual competition information) · connection state (a scoreboard that has lost its live data feed says so, rather than silently freezing on stale data) · stale-data warning.

**Avoid dense navigation or administrative controls** — restated absolutely; a scoreboard is a display surface, not an interactive application, and never exposes any control beyond what a venue operator's separate, authenticated interface provides.

## 4. Report and Print Experience

Official, structured, reproducible, print-safe — extending the Print theme from [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture). A printed official document (a certified result sheet, a medal-tally report, an accreditation credential) reflects the exact certified/published version it was generated from, per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession) — reproducibility of historical documents is a design requirement, not an incidental property.

## 5. Device and Browser Compatibility

| Category | Direction |
|---|---|
| Supported browsers | A candidate baseline (current-version evergreen browsers: Chrome, Firefox, Safari, Edge) — **not finalized without evidence**, per the phase's own working instruction |
| Desktop sizes | Per [responsive-touch-keyboard-and-device-behavior.md, Section 1](responsive-touch-keyboard-and-device-behavior.md#1-responsive-architecture) |
| Tablet sizes | Same |
| Mobile sizes | Same |
| Android versions | A candidate minimum-supported-version policy, informed by [../05-devops/deployment-strategies-rollbacks-and-maintenance.md, Section 9](../05-devops/deployment-strategies-rollbacks-and-maintenance.md#9-mobile-client-and-api-backward-compatibility) — not finalized here |
| Device classes | Scanner hardware, kiosk displays, scoreboard displays — each with its own dedicated theme/mode per [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture) |
| Slow networks | Per [flutter-mobile-experience-architecture.md, Section 4](flutter-mobile-experience-architecture.md#4-low-bandwidth-experience) |
| Intermittent networks | Per [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) |
| High-latency links | Same low-bandwidth-experience principles apply |
| Low-memory devices | Glass UI and heavy animation are avoided on these devices specifically, per [color-theme-and-surface-system.md, Section 5](color-theme-and-surface-system.md#5-glass-ui-rules) |

**Do not finalize a compatibility matrix without evidence** — restated absolutely per the phase's own working instruction; every value in the table above is a candidate, not a commitment.

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably final browser/Android-version support baseline and whether kiosk hardware is DepEd-procured (affecting achievable touch-target/display specifications) or a candidate for future planning.
