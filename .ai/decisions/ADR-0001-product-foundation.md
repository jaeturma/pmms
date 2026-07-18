# ADR-0001: PMMS Product Foundation — Enterprise, Configurable, Multi-Meet Platform

## Status

Accepted (as a Phase 0.1 product-direction decision; pending formal stakeholder and architecture sign-off — see [../current-phase.md](../current-phase.md))

## Context

PMMS is a new platform for DepEd, currently at the earliest stage of definition (Phase 0.1 — Product Vision, Scope, Operating Model, Stakeholders, and Success Criteria). The repository at this point contains only a fresh Laravel 13 + React 19 + Inertia starter-kit installation with no PMMS-specific domain code.

Before any domain modeling or implementation begins, a foundational product-direction decision is needed: should PMMS be conceived and built as a narrow, single-purpose tool for one meet (e.g., a registration form or a medal-tally spreadsheet replacement), or as a broader, reusable, enterprise-grade platform intended to operate the full lifecycle of DepEd sports meets across multiple meet cycles and, eventually, multiple organizational levels?

This choice has significant downstream consequences for scope, architecture, and stakeholder expectations, and needed to be made explicit and documented before Phase 0.2 (Domain Discovery and Bounded Context Architecture) begins.

## Decision

PMMS will be treated as a **configurable, multi-meet, enterprise-grade platform** — not a one-time event website and not a basic registration or medal-tally system.

Specifically:

1. PMMS's scope spans the full meet lifecycle (30 stages, from strategic planning through historical analytics — see [../../docs/00-product/phase-0.1-product-foundation.md, Section 15](../../docs/00-product/phase-0.1-product-foundation.md#15-meet-lifecycle)), not merely registration and results.
2. The platform is designed to support multiple meets over time, with clear separation between permanent master data and meet-specific data (see [../../docs/00-product/operating-model.md](../../docs/00-product/operating-model.md)).
3. Sport, event, and committee structures are configuration-driven rather than hard-coded per meet.
4. The platform is built to commercial-product engineering standards (tested, observable, documented, recoverable, upgrade-safe) from the outset, even though its initial deployment target is a single DepEd organization (see [../../docs/00-product/phase-0.1-product-foundation.md, Section 18](../../docs/00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction)).
5. High-integrity domains (official results, eligibility, medical data, accreditation, medal tally) require human validation and are never fully automated, including by AI (see [../../docs/00-product/phase-0.1-product-foundation.md, Section 19](../../docs/00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction)).

## Rationale

- The business problem (Section 6 of the product foundation document) spans the entire meet lifecycle — fragmented spreadsheets, manual reconciliation, delayed results, lost institutional knowledge — not just registration or tallying. A narrow tool would leave most of these problems unaddressed.
- The confirmed technology direction (Laravel 13, React 19, Inertia, Flutter, Redis, Horizon, Reverb, MinIO) is enterprise-grade infrastructure, signaling an intent to build a durable platform rather than a disposable event microsite.
- DepEd's operating reality — provincial meets recurring across cycles, with potential future expansion to division/regional/national levels — makes a reusable, configurable platform materially more valuable than a single-use system, even if the initial deployment target remains one organization and one meet at a time.
- Treating high-integrity domains as requiring human validation, rather than full automation, protects institutional trust and reduces legal/reputational risk, consistent with the working rules governing this project.

## Consequences

**Positive:**
- Architecture and domain modeling in Phase 0.2 can proceed with a clear mandate to design for configurability and reuse, avoiding one-off hard-coded solutions that would need to be rebuilt for the next meet.
- Stakeholders are set accurate expectations: PMMS is a multi-year platform investment, not a quick one-off build.
- Commercial-quality engineering practices (testing, observability, documentation) are established as a baseline expectation from the start, rather than retrofitted later.

**Negative / trade-offs:**
- Initial delivery will take longer than a narrow single-purpose tool would, because the architecture must accommodate configurability and multi-meet operation even for the first release.
- Some enterprise-readiness work (tenant isolation readiness, API readiness, white-label readiness) has cost even though multi-organization use is not an initial requirement (see OD-02 and OD-22 in [../../docs/00-product/open-decisions.md](../../docs/00-product/open-decisions.md)).
- This direction increases the importance of resolving high-priority open decisions (eligibility authority, result approval chain, sports rule source, medal tally rules, medical-data handling, AI-service restrictions) before Phase 0.2 domain design, since a broader platform touches more high-integrity domains at once.

## Alternatives Considered

1. **Narrow, single-meet registration/tally tool.** Rejected — would not address the majority of documented business problems (scheduling, officiating, accreditation, logistics, institutional knowledge loss) and would likely need a costly rebuild to support a second meet cycle.
2. **Full multi-tenant SaaS product for arbitrary organizations from day one.** Rejected for the initial direction — DepEd is confirmed as the primary organization and no other tenant is currently in scope (see OD-02); building full multi-tenancy immediately would add cost without a validated near-term need. Tenant-isolation *readiness* is retained as an architectural principle instead.
3. **Fully automated (AI-driven) eligibility, scoring, and medal-tally decisions.** Rejected — conflicts with the working rules for this project and with institutional trust requirements for high-integrity domains; human validation is retained as a hard requirement.

## Validation Requirements

This decision is provisional pending:

- Formal review and sign-off by DepEd Leadership and the Meet Organizing Committee (see [../../docs/00-product/stakeholder-register.md](../../docs/00-product/stakeholder-register.md)).
- Resolution of the high-priority open decisions listed in [../current-phase.md](../current-phase.md).
- Confirmation of the initial deployment scope (OD-01), single vs. multi-organization direction (OD-02), and single vs. multi-meet launch (OD-03) in [../../docs/00-product/open-decisions.md](../../docs/00-product/open-decisions.md).

## Related Documents

- [../../docs/00-product/phase-0.1-product-foundation.md](../../docs/00-product/phase-0.1-product-foundation.md)
- [../../docs/00-product/product-scope.md](../../docs/00-product/product-scope.md)
- [../../docs/00-product/operating-model.md](../../docs/00-product/operating-model.md)
- [../../docs/00-product/open-decisions.md](../../docs/00-product/open-decisions.md)
- [../project-context.md](../project-context.md)
- [../current-phase.md](../current-phase.md)
