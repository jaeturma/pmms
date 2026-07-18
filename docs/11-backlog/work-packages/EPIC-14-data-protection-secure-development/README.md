# EPIC-14 — Data Protection, Privacy, and Secure Development Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release E
**Status:** Planned — Not Started

## Purpose

Implement cross-cutting safeguards before sensitive PMMS modules are built: data classification, masking, log redaction, export readiness, rate limiting, header/cookie/CSRF hardening reviews, secret hygiene, and support/impersonation restriction. Per [../../phase-1-execution-sequence.md](../../phase-1-execution-sequence.md), this epic needs only WP-02-02 and should start in parallel with Release B — its constants and redaction rules are consumed by EPIC-08, EPIC-10, and EPIC-13.

## Architecture Sources

[../../../03-security/](../../../03-security/), ADR-0006.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-14-01](WP-14-01-data-classification-constants-and-contracts.md) | Data Classification Constants and Contracts | Small | P1 |
| [WP-14-02](WP-14-02-sensitive-data-masking-foundation.md) | Sensitive Data Masking Foundation | Medium | P1 |
| [WP-14-03](WP-14-03-log-redaction-foundation.md) | Log Redaction Foundation | Medium | P1 |
| [WP-14-04](WP-14-04-secure-export-readiness.md) | Secure Export Readiness | Small | P2 |
| [WP-14-05](WP-14-05-rate-limiting-baseline.md) | Rate Limiting Baseline | Small | P1 |
| [WP-14-06](WP-14-06-security-header-and-cookie-review.md) | Security Header and Cookie Review | Small | P1 |
| [WP-14-07](WP-14-07-csrf-session-and-request-protection-review.md) | CSRF, Session, and Request Protection Review | Small | P1 |
| [WP-14-08](WP-14-08-secret-and-environment-hygiene-checks.md) | Secret and Environment Hygiene Checks | Small | P1 |
| [WP-14-09](WP-14-09-support-and-impersonation-restriction-foundation.md) | Support and Impersonation Restriction Foundation | Medium | P1 |
| [WP-14-10](WP-14-10-privacy-and-security-regression-tests.md) | Privacy and Security Regression Tests | Medium | P2 |

## Dependencies

WP-02-02 (Hard). Cross-epic: WP-13-01 (structured logging, consumed by log redaction), WP-03-03 (session security, reviewed by WP-14-07), WP-05-07 and WP-06-06 (consumed by impersonation restriction).

## Completion Outcome

Data-classification constants, sensitive-data masking, log redaction, secure-export readiness, rate limiting, security-header/cookie review, CSRF/session/request-protection review, secret hygiene checks, and support/impersonation restriction, closed out with regression tests.

## Deferred Items

None — this epic is itself the cross-cutting safeguard layer other epics depend on. Final numeric retention values and classification-tier formal validation (PD-04, SD-09) remain Phase 0 open decisions; the constants here are structured to absorb those decisions without rework.

## Risks

RISK-EPIC14-01 — a minor-athlete or medical-data logging shortcut introduced by a later epic (per ARR-10 in the Phase 0.13 risk register) would be caught here only if WP-14-10 is genuinely exhaustive; the regression suite's classification-coverage mapping exists precisely for that.
