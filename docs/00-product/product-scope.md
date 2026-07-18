# PMMS Product Scope

**Status:** Draft for Architecture and Stakeholder Validation
**Related:** [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md) · [open-decisions.md](open-decisions.md)

This document defines what PMMS covers, in what sequence, and what is explicitly deferred or excluded. It is a scope framework, not a feature backlog — user stories, acceptance criteria, and detailed feature specifications belong to later phases (Phase 0.2 onward).

---

## 1. Scope Decision Rules

Scope decisions for PMMS should be evaluated against the following rules, in order:

1. **Integrity first** — a capability that touches official results, eligibility, medical data, or medal tally is never simplified in a way that compromises traceability or human validation (see Product Principles in [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#8-product-principles)).
2. **Lifecycle coverage over feature depth** — early releases should cover the full meet lifecycle shallowly before any single stage is built deeply, so no stage becomes a bottleneck that forces organizers back to spreadsheets.
3. **Configuration over one-off code** — if a capability varies by sport, meet, or committee, it is scoped as a configurable capability, not a one-time hard-coded feature.
4. **Field-usability over administrative completeness** — capabilities used live at a venue (scanning, score entry, officiating) are prioritized for usability and offline resilience over administrative back-office completeness.
5. **Defer what requires unavailable authority** — capabilities that depend on official rules, policies, or data DepEd has not yet provided (see Rule 8 of the working rules) are deferred until that input exists, rather than invented.

## 2. Scope Change Process

Any change to what is in-scope, deferred, or out-of-scope should:

1. Be recorded as a proposed change with rationale.
2. Be checked against the Scope Decision Rules above.
3. Be reflected in this document and, if it affects vision/boundaries, in [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md).
4. Be logged with a decision date and owner once approved.

A formal change-log mechanism (e.g., a scope decision register) is a candidate for Phase 0.2 process design; this document does not mandate a specific tool.

---

## 3. Minimum Viable Provincial Meet Scope

The minimum viable scope is the smallest set of capabilities that would let a single provincial meet run on PMMS instead of spreadsheets, without compromising result integrity. **This is a proposed MVP boundary requiring stakeholder validation, not a committed release plan.**

Candidate MVP capabilities, drawn from the meet lifecycle:

- Meet creation and basic configuration (dates, venues, sports/events list)
- Delegation and school registration
- Athlete and coach registration
- Basic eligibility submission and manual validation workflow
- Basic accreditation (identity credentialing, not necessarily QR-based at MVP)
- Entry submission per sport/event
- Manual or semi-automated scheduling
- Score entry per event
- Result validation (human-in-the-loop)
- Basic medal tally computed from validated results
- Basic public portal (schedules and results)
- Basic reporting (rosters, result sheets)

Explicitly deferred from MVP unless validation shows otherwise: advanced bracket automation, offline mobile synchronization, AI-assisted features, multi-meet cross-organization support, and advanced analytics.

## 4. Enterprise Product Scope

Beyond MVP, the enterprise product direction (see [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#18-commercial-quality-product-direction)) anticipates:

- Multi-meet, multi-organization support
- Configurable branding and white-label readiness
- Full offline-capable mobile operations
- AI-assisted administrative workflows (advisory only — see Section 19 of the foundation document)
- Advanced analytics and historical reporting across meets
- API-based integration with external/adjacent systems
- Tenant isolation for potential SaaS deployment

---

## 5. In-Scope Capabilities (Product Program)

Organized by lifecycle area, consistent with [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#9-product-scope-summary):

| Area | In Scope |
|---|---|
| Meet planning | Meet creation, configuration, calendar, venue/resource planning |
| Organization setup | Organization, region/division/district hierarchy configuration |
| Committee management | Committee formation, assignment, task tracking |
| Delegation management | Delegation registration, onboarding, roster management |
| Registration | Athlete and coach registration, profile management |
| Eligibility | Eligibility submission, document upload, validation workflow |
| Accreditation | Credentialing for athletes, coaches, officials, committee, media, and support personnel |
| Sports and event setup | Configurable sports/events/formats |
| Entries | Entry submission, entry validation |
| Scheduling | Tournament scheduling, seeding, draws, brackets, heats, lane assignment |
| Officiating | Officials assignment, technical official workflows |
| Scoring | Score entry, result validation, protest/appeal tracking |
| Standings | Medal tally, team standings |
| Support operations | Medical, food, transportation, billeting, finance, security, ICT coordination |
| Communication | Media/communications coordination, public advisories |
| Public information | Public portal for schedules, results, medal tally, advisories |
| Mobile operations | Field-facing mobile applications for organizers, officials, scanners |
| Reporting | Analytics, reporting, official document generation |
| AI assistance | Advisory-only AI features per Section 19 of the foundation document |
| Compliance | Audit trail and compliance reporting |

## 6. Future-Scope Capabilities

Capabilities considered valuable but not assumed for initial releases:

- Regional and national meet support (architecture should anticipate this; initial delivery targets provincial)
- Multi-organization SaaS deployment
- Deep integration with adjacent DepEd systems (learner information systems, HR systems)
- Advanced AI-assisted scheduling optimization
- Livestream integration (note: PMMS is not itself a livestream production platform — see Out-of-Scope)
- Public athlete performance history across multiple meets/years
- Advanced predictive analytics

## 7. Out-of-Scope Capabilities

Consistent with [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#10-product-boundaries), the following are out of scope for the PMMS product program:

- Full DepEd learner information system
- Replacement for DepEd human resource systems
- General school management platform
- Sports-betting functionality of any kind
- Autonomous (non-human-validated) eligibility determination
- Autonomous (non-human-validated) score or medal changes
- Full accounting system replacement
- Full hospital/clinical information system
- Public social-media network functionality
- Livestream production platform
- Substitute authority for official sports governing bodies' rules

## 8. Deferred Integrations

The following integrations are conceivable but deferred pending later-phase decisions and DepEd input:

- DepEd learner information system integration (identity/eligibility source)
- DepEd HR or personnel systems (for official/staff identity)
- School master-data systems
- Payment gateways (if finance workflows require them)
- SMS/communication gateways for advisories
- External sports federation systems
- Government ID or civil registry verification services

See [open-decisions.md](open-decisions.md) for related open questions on integration requirements.

---

## 9. Public Portal Scope

**In scope (initial):** published schedules, published results, medal tally, public advisories/announcements.

**Deferred:** public athlete profile pages beyond meet-scoped participation, historical cross-meet public analytics, public API access.

**Out of scope:** any public display of medical data, unvalidated/working results, or personally sensitive information beyond what is approved for publication.

## 10. Mobile Scope

**In scope (initial release target, subject to validation):** field data capture, accreditation scanning, score entry support, schedule/roster lookup for field staff.

**Future scope:** full offline-first synchronization across all workflows, public-facing mobile app.

Flutter is the confirmed technology direction for mobile (see repository technology stack); no mobile screens or architecture are defined in this document.

## 11. Committee Scope

Each committee type identified in [stakeholder-register.md](stakeholder-register.md) is expected to have its own operational workflows within PMMS (e.g., medical incident logging, transportation manifests, billeting assignments). The specific workflow design per committee is deferred to Phase 0.2 domain discovery; this document only confirms that committee-level operational scope is part of the product.

## 12. AI Scope

See [phase-0.1-product-foundation.md, Section 19](phase-0.1-product-foundation.md#19-ai-assisted-product-direction) for the authoritative list of possible AI-assisted capabilities and strict limitations. AI scope is advisory-only in all high-integrity domains for the entirety of the current product program unless a future approved DepEd policy states otherwise.

## 13. Reporting Scope

**In scope:** operational reports (rosters, entry lists, result sheets, tally reports), compliance/audit reports, post-event summary reports.

**Deferred:** cross-meet longitudinal analytics, public-facing data exports, custom report-builder tooling.

## 14. Data Migration Scope

No existing PMMS data currently exists to migrate (see repository inspection findings in the completion report). Data migration scope, if any, would apply to importing historical spreadsheet-based records from prior manually run meets. This is **not assumed** as a requirement and requires confirmation from DepEd on whether historical records exist in a usable format and should be imported.

## 15. Integration Scope

No integrations are committed in Phase 0.1. See Section 8 (Deferred Integrations) above and [open-decisions.md](open-decisions.md) for the integration-requirements open question.

---

## 16. Release Sequencing (Directional, Not Committed)

| Horizon | Focus |
|---|---|
| **Initial release** | Minimum viable provincial meet scope (Section 3), single meet, core lifecycle coverage, human-validated results |
| **Subsequent release** | Expanded committee workflows, accreditation with QR validation, mobile field operations, initial AI-assisted detection features (duplicate athletes, missing requirements) |
| **Long-term platform direction** | Multi-meet/multi-organization support, full offline mobile synchronization, advanced analytics, regional/national scaling, white-label/SaaS readiness |

This sequencing is directional and intended to guide architecture decisions toward extensibility; it is not a committed delivery roadmap with dates.
