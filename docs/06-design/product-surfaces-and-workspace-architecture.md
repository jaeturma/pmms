# PMMS Product Surfaces and Dashboard Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/application-architecture.md](../01-architecture/application-architecture.md) · [information-architecture-and-navigation.md](information-architecture-and-navigation.md) · [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary)

This document defines PMMS's nine product surfaces and dashboard architecture. **No page, layout, or dashboard widget code is created here.**

---

## 1. Product Surfaces

| Surface | Character |
|---|---|
| Administrative and Operations Portal | High-density, role-aware, workflow-oriented |
| Tournament Management Workspace | Competition-focused, schedule-aware, state-driven |
| Technical Official Workspace | Fast entry, assignment-aware, low-distraction |
| Public Portal | Read-only, scalable, accessible, privacy-filtered |
| Flutter Mobile Application | Field-oriented, task-focused, offline-aware |
| Accreditation and Scanner Interface | Minimal steps, large feedback, device-bound |
| Kiosk | Touch-friendly, privacy-safe, session-limited |
| Public Scoreboard and Venue Display | High contrast, large text, distance-readable |
| Report and Print Experience | Official, structured, reproducible, print-safe |

Every surface maps to the runtime boundaries already established in [../01-architecture/application-architecture.md](../01-architecture/application-architecture.md) and [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary) — this document does not introduce a new surface beyond what Phase 0.4 already scoped; it gives each surface its experience character.

## 2. Dashboard Architecture

### Dashboard Layers

| Dashboard | Content Focus |
|---|---|
| Personal Dashboard | Assignments, pending tasks, alerts, deadlines |
| Meet Dashboard | Meet readiness, active events, operational status |
| Committee Dashboard | Committee-specific tasks, issues, deliverables |
| Sport Dashboard | Entries, schedule, officials, tournament progress |
| Venue Dashboard | Readiness, active events, incidents, devices |
| Executive Dashboard | High-level readiness, risk, participation, results |
| Public Dashboard | Published schedules, results, medal tally, advisories |

**Dashboards prioritize actionable information over decorative metrics** — restated absolutely as the section's governing rule.

### Dashboard Content Rules

Every card or widget answers at least one question: What requires attention? · What changed? · What is at risk? · What is next? · What is delayed? · What is complete? · What can this user act on? · How fresh is the information?

A widget that cannot answer at least one of these questions is a vanity metric and is not included — restated absolutely; a dashboard's value is measured by the decisions it enables, not its visual completeness.

## 3. Surface-to-Dashboard Mapping

| Product Surface | Primary Dashboard(s) |
|---|---|
| Administrative and Operations Portal | Personal, Meet, Executive |
| Tournament Management Workspace | Sport, Personal |
| Technical Official Workspace | Personal (assignment-scoped, minimal) |
| Public Portal | Public |
| Flutter Mobile Application | Personal (task-focused subset) |
| Kiosk | Public (self-service subset) |
| Public Scoreboard and Venue Display | None — a scoreboard is a display, not a dashboard; see [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md) |

## 4. Workspace-to-Surface Relationship

Every surface in Section 1 operates within the workspace hierarchy established in [information-architecture-and-navigation.md, Section 1](information-architecture-and-navigation.md#1-workspace-hierarchy) — the Administrative Portal and Tournament Management Workspace both traverse Organization → Meet → Committee/Sport/Venue context, while the Public Portal and Scoreboard surfaces operate at a deliberately shallower Meet → published-projection level, consistent with their non-authoritative, read-only role per [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md).

## 5. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether the Technical Official Workspace is a genuinely distinct surface or a filtered view of the Administrative Portal (an implementation-sequencing question, not yet resolved) and Executive Dashboard content scope.
