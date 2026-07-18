# PMMS Mobile, Offline, and Public AI Boundaries

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../06-design/flutter-mobile-experience-architecture.md](../06-design/flutter-mobile-experience-architecture.md) · [../06-design/public-portal-kiosk-scoreboard-and-display-experience.md](../06-design/public-portal-kiosk-scoreboard-and-display-experience.md)

This document defines AI boundaries for mobile, offline, and public-facing contexts — the three surfaces where AI's exposure and trust assumptions differ most from the authenticated administrative portal. **No mobile, offline, or public AI feature is implemented here.**

---

## 1. Offline AI Boundaries

**No final consequential AI decision occurs offline** — restated absolutely, directly extending the platform-wide "AI never finalizes a high-integrity decision" rule with the offline-specific reinforcement that even AI *suggestions* requiring server-side grounding cannot function meaningfully without connectivity.

| Element | Direction |
|---|---|
| No final consequential AI decisions offline | Restated absolutely |
| Avoid storing sensitive AI prompts locally | A device's local store never retains a Restricted-or-above AI prompt/response beyond the immediate session |
| Mobile AI uses server-controlled requests when online | AI processing occurs server-side via the AI Gateway (per [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md)), never on-device model inference for consequential capabilities |
| Cached help content may be available offline | UC-09/UC-10's help documentation (non-generative, static content) is a candidate for offline caching — the generative AI response itself is not |
| Approved lightweight deterministic checks may run locally | Restated from [ai-use-case-and-risk-classification.md, "Tier 0"](ai-use-case-and-risk-classification.md#2-risk-tiers) — a deterministic, non-AI validation (e.g., a required-field check) may run on-device; AI inference does not |
| AI-generated outputs show freshness | Any cached AI output visible offline displays when it was generated, per [../06-design/dashboard-table-chart-and-data-visualization-standards.md, Section 4](../06-design/dashboard-table-chart-and-data-visualization-standards.md#4-data-freshness-version-and-state-indication) |
| Pending AI request state is visible | A queued-but-not-yet-processed AI request is shown as pending, never silently dropped |
| Device loss must not expose retained sensitive outputs | Restated from [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security) — encrypted local storage applies equally to any locally-cached AI content |

## 2. Mobile AI Boundaries

Mobile AI capabilities (where applicable — most of the 13 use cases are administrative-portal-first) follow [../../.ai/mobile-experience-rules.md](../../.ai/mobile-experience-rules.md)'s low-bandwidth and shared-device discipline, extended with: an AI request from a Flutter client passes through the same AI Gateway authorization/audit path as a web request — never a separate, less-controlled mobile-specific path. Shared-device sessions (per [../06-design/accreditation-qr-device-and-shared-station-experience.md, Section 3](../06-design/accreditation-qr-device-and-shared-station-experience.md#3-shared-device-and-station-experience)) never allow one operator's AI conversation history to leak to the next operator on the same device.

## 3. Public AI

Public AI must: use only public knowledge and public projections (restated absolutely, per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections)) · apply strict rate limits (per [../03-security/application-api-and-client-security.md, Section 2](../03-security/application-api-and-client-security.md#2-api-security)) · avoid personal-data lookup · avoid unpublished results · avoid eligibility, medical, guardian, security, and finance data — restated absolutely, no exception · provide citations · display limitations · refuse private-data requests · **prevent prompt-based scope escalation** — restated absolutely; a public user cannot, through clever prompting, trick a public-facing AI capability into revealing data beyond its Public-tier, unauthenticated scope.

### Public AI Service Identity (Cross-Reference)

Restated from [ai-identity-authorization-scope-and-audit.md, Section 6](ai-identity-authorization-scope-and-audit.md#6-public-ai) — a public AI capability uses a dedicated, narrowly-scoped service identity, never the same identity used for an internal, authenticated capability.

## 4. Prompt-Based Scope Escalation (Defense)

The primary defense against a public user attempting to escalate scope via prompting is architectural, not prompt-based: the AI Gateway's Context and Authorization Resolver (per [ai-platform-and-service-architecture.md, Section 1](ai-platform-and-service-architecture.md#1-conceptual-components)) determines the requester's actual scope (unauthenticated → Public-tier only) **before** the request reaches the model — no amount of clever prompting can expand what data is ever assembled into the request's context in the first place, since the data simply isn't retrieved for an unauthenticated scope.

## 5. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether any of the 13 use cases is ever exposed as a public-facing capability at all (currently, UC-12's policy search is the most plausible public candidate, restricted to public-classified sources only) and mobile AI rollout sequencing relative to `mobile/` scaffolding (per [../06-design/design-open-decisions.md, DX-18](../06-design/design-open-decisions.md#dx-18--mobile-scaffolding-timing-cross-reference)).
