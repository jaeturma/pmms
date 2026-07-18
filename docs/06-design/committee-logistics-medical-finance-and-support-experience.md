# PMMS Committee, Logistics, Medical, Finance, and Support Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md) · [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md)

This document defines experience needs for the twelve operational committees, with dedicated depth for Medical and Finance given their elevated sensitivity. **No screen or component is created here.**

---

## 1. Committee Experience Architecture

For each committee, this document names core experience needs rather than complete screens, per working rule 16 ("Do not create production copy for every page"):

| Committee | Primary Tasks | Time Pressure | Mobile Need | Offline Need | Sensitive Data | Key Alerts |
|---|---|---|---|---|---|---|
| Secretariat | Registration processing, correspondence | Moderate | Low | Low | Confidential | Deadline approaching |
| Tournament managers | Draws, brackets, official assignment | High | Moderate | Moderate | Internal | Schedule conflict |
| Technical officials | Event oversight, rule enforcement | Critical | High | High | Internal | Assignment change |
| Tally team | Medal tally encoding/certification | High | Low | Low | Internal | Recalculation needed |
| Medical | Incident response, clearance review | Critical | High | Moderate | Highly Restricted | New incident |
| Food | Meal entitlement tracking | Moderate | High | High | Internal | Distribution shortfall |
| Transportation | Trip coordination | Moderate | High | Moderate | Internal | Schedule delay |
| Billeting | Accommodation assignment | Low–Moderate | Low | Low | Internal | Capacity conflict |
| Finance | Budget/expense processing | Low–Moderate | Low | Low | Restricted | Approval pending |
| Security | Incident logging, access oversight | Critical | High | Moderate | Highly Restricted | Active incident |
| ICT | Device/system support | High (reactive) | Moderate | Low | Internal | System degraded |
| Media | Public content coordination | Moderate | Low | Low | Internal–Public | Publication pending review |

Each committee's dashboard needs, reports, handoffs, and approval actions are derived from this table and the relevant bounded context in [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) — this document does not re-derive committee ownership already established there.

## 2. Medical Experience

**Requires the strongest privacy boundary of any PMMS surface.** Summary and detailed views are architecturally distinct (per [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Medical Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#medical-data-governance)) — a non-Medical-role user never sees more than the minimal clearance-status flag. Emergency context (rapid access for a genuine medical response) is a distinct, elevated interaction path from routine review. Minimal public visibility (never, under any circumstance, per working rule 26). Fast incident entry (minimizing steps during an actual emergency). Restricted attachments (per [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md)). Audit visibility (every access is recorded and, for the affected committee, reviewable). Offline caution (per [flutter-mobile-experience-architecture.md, Section 2](flutter-mobile-experience-architecture.md#2-offline-mobile-experience), only a minimal emergency-relevant flag is ever offline-cached, never full medical detail). Break-glass indication (if emergency access is ever invoked, the interface makes this unambiguous and irreversible-looking, matching its actual audit weight). Export restrictions and support-access restrictions restated absolutely from Phase 0.6.

**No clinical protocol is defined here** — restated absolutely per working rule 34's spirit extended to medical practice specifically; this document defines interface patterns only, never medical decision logic.

## 3. Finance Experience

Clear amounts and currency (per [../02-data/database-naming-and-design-standards.md, Section 5](../02-data/database-naming-and-design-standards.md#5-monetary-value-standards) — fixed-precision, explicit currency, never ambiguous) · approval status (visually distinct per [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md)) · supporting documents (per [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md)) · encoder and approver distinction (restated absolutely from SOD-06 — the interface itself never allows the same user to both encode and approve the same transaction) · correction and reversal history (per [high-integrity-approval-certification-and-publication-ux.md, Section 4](high-integrity-approval-certification-and-publication-ux.md#4-correction-and-supersession-ux)) · export restrictions · audit trail · privacy · **no public display except approved summaries** — restated absolutely per working rule 26.

## 4. Medical-Alert, Billeting, Food-Distribution, Transport, Security-Incident, ICT-Support, and Media Interfaces

| Interface | Core Pattern |
|---|---|
| Medical-alert interfaces | A high-visibility, minimal-friction incident-capture flow, feeding into Section 2's privacy boundary immediately upon entry |
| Billeting interfaces | Roster-style assignment tracking with capacity/conflict visualization, sharing the table-standards pattern in [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) |
| Food-distribution interfaces | Fast, high-volume scan-and-confirm flows, sharing the QR-interaction pattern in [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md) where entitlement validation is QR-based |
| Transport interfaces | Schedule-timeline-based coordination views, per [sports-tournament-scoring-and-results-components.md, Section 11](sports-tournament-scoring-and-results-components.md#11-venue-boards-and-schedule-timelines) |
| Security incident interfaces | A fast-capture, high-priority pattern mirroring medical-alert interfaces, classified Highly Restricted per [../03-security/privacy-by-design-architecture.md, Section 3](../03-security/privacy-by-design-architecture.md#3-personal-data-inventory) |
| ICT support interfaces | Diagnostic-detail-oriented views for device/system status, per [accreditation-qr-device-and-shared-station-experience.md, Section 4](accreditation-qr-device-and-shared-station-experience.md#4-device-status-and-health-indication) |
| Media and public-information interfaces | Preview-before-publish workflows, per [high-integrity-approval-certification-and-publication-ux.md, Section 5](high-integrity-approval-certification-and-publication-ux.md#5-publication-ux) |

## 5. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether each committee interface is a fully dedicated workspace or a filtered view of a shared operational interface, and medical break-glass interface treatment specifics.
