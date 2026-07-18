# PMMS Tenant Metering, Licensing, Subscription, and Billing Readiness

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [tenant-configuration-branding-entitlement-and-quota-architecture.md](tenant-configuration-branding-entitlement-and-quota-architecture.md) · [../00-product/open-decisions.md, OD-22](../00-product/open-decisions.md#od-22--licensing-model)

**No billing, pricing, or metering implementation is created here.**

---

## 1. Licensing and Subscription Readiness

Readiness defined for: editions · plans · subscriptions · trials · entitlement periods · renewals · suspensions (per [tenant-onboarding-suspension-offboarding-and-portability.md, Section 2](tenant-onboarding-suspension-offboarding-and-portability.md#2-tenant-suspension-new-in-phase-012)) · grace periods · contract-specific features · usage-based add-ons · support plans · dedicated deployments.

This directly extends [OD-22](../00-product/open-decisions.md#od-22--licensing-model) (Licensing Model, still Open) — readiness architecture, not a licensing-model decision.

## 2. Metering Architecture

Metered conceptually: active users · athletes · meets · storage · AI consumption (none active) · API usage · notifications · public traffic · devices · reports · exports.

| Requirement | Direction |
|---|---|
| Tenant-isolated | A metering record never aggregates across tenants except in approved, de-identified platform-wide reporting |
| Reproducible | A metering value can be independently recomputed from durable source records, never trusted as a sole running counter alone |
| Auditable where used for billing | Any metering feeding a future billing process requires the same audit rigor as any other financial-adjacent record |
| Versioned | A change to what counts toward a metered quantity is versioned, never silently redefined mid-period |
| Resistant to duplication | Metering respects the same idempotency discipline as any other counted action, per [../08-workflows/outbox-inbox-idempotency-and-message-reliability.md, Section 4](../08-workflows/outbox-inbox-idempotency-and-message-reliability.md#4-idempotency) |
| Reconciled | Metered totals are periodically reconciled against source-of-truth counts, discrepancies surfaced for review, never silently trusted |

## 3. Commercial Edition Readiness

Candidate editions (illustrative, not committed): a DepEd-governed edition and a future commercial edition for other organizations — contingent entirely on [OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization) and OD-22 resolution.

## 4. Contractual Service-Level Readiness

A future contractual service level is derived only from measured, proven internal SLOs (per [performance-budget-and-service-level-architecture.md, Section 2](performance-budget-and-service-level-architecture.md#2-service-level-architecture)) — restated absolutely, no contractual commitment is implied or offered in this phase.

## 5. Customer Support Readiness

Tenant support tiers are a candidate future entitlement dimension (Section 5, [tenant-configuration-branding-entitlement-and-quota-architecture.md](tenant-configuration-branding-entitlement-and-quota-architecture.md#5-feature-entitlements)) — readiness only; the existing support model (per [../05-devops/production-support-access-and-data-repair-operations.md](../05-devops/production-support-access-and-data-repair-operations.md)) remains the current, single-tier operating model.

## 6. Cross-Tenant Analytics Restrictions and Benchmarking (Cross-Reference)

Full detail: [reporting-search-analytics-and-data-platform-readiness.md, Section 4](reporting-search-analytics-and-data-platform-readiness.md#4-cross-tenant-analytics). Benchmarking (comparing one tenant's metrics against de-identified aggregate platform data) follows the identical cross-tenant analytics governance — approved purpose, de-identification, controlled access, audited output.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-15 (licensing-model decision, tied to OD-22) and ED-16 (metering-to-billing integration timing).
