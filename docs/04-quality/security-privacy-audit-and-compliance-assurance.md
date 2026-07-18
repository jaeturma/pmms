# PMMS Security, Privacy, Audit, and Compliance Assurance Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../03-security/security-testing-and-assurance.md](../03-security/security-testing-and-assurance.md) · [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md) · [../03-security/compliance-control-framework.md](../03-security/compliance-control-framework.md)

This document extends [../03-security/security-testing-and-assurance.md](../03-security/security-testing-and-assurance.md) (Phase 0.6) with quality-engineering-specific test detail for authorization, audit, security, privacy, and compliance-evidence testing. **No test code or security-scanning tool is configured here.**

---

## 1. Authorization Testing

Every enforcement point tests the full Phase 0.3 formula:

Role · Permission · Scope · Assignment · Time Validity · Resource State · Data Classification · Device Trust · Explicit Deny · Security Hold · Separation of Duties · Cross-Organization Isolation · Cross-Meet Isolation · Cross-Committee Isolation · Cross-Delegation Isolation · Cross-Sport Isolation · Cross-Venue Isolation · Privileged Access · Revocation · Impersonation (if implemented) · Break-Glass (if implemented) · Offline Authorization · AI Authorization.

Every permission in [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) requires both a positive (correctly granted) and negative (correctly denied) test case — restated from working rule 33 ("Require negative, denial, conflict, recovery, and concurrency tests"). Every SoD conflict (SOD-01 through SOD-11) requires a negative test confirming the conflicting combination is rejected. **Do not validate authorization through frontend visibility alone** — restated absolutely from working rule 31; every authorization test asserts server-side behavior, never merely that a UI element is hidden.

## 2. Audit Testing

| Target | What to Verify |
|---|---|
| Actor | The recorded actor matches the actual authenticated identity |
| Effective user | Correctly distinguished from an impersonating actor where applicable |
| Impersonating user | Recorded distinctly, never blended with the effective user, per [../03-security/audit-and-security-event-architecture.md, Section 5](../03-security/audit-and-security-event-architecture.md#5-impersonation-auditing) |
| Action | The recorded action matches the actual operation performed |
| Target | The recorded target matches the actual affected resource |
| Scope | The recorded scope (organization/meet/committee) matches the action's actual context |
| Reason | Present wherever required (corrections, privileged access, exports) |
| Previous and new state references | A correction's audit event correctly references both states |
| Device | Device-attributed actions correctly distinguish device from operator identity |
| Correlation ID | Related events share a traceable correlation ID |
| Result | Success/failure/denied is correctly recorded |
| AI involvement | AI-assisted actions correctly flag AI involvement, per [../03-security/audit-and-security-event-architecture.md, Section 7](../03-security/audit-and-security-event-architecture.md#7-ai-assistance-auditing) |
| Sensitive views | Every access beyond minimal normal exposure to Restricted/Highly Restricted data produces an audit event |
| Exports | Every export is audit-relevant |
| Downloads | Every Restricted/Highly Restricted-tier download is audit-relevant |
| Privileged actions | Every privileged-category action is audit-relevant |
| Break-glass | If implemented, every invocation is fully recorded per [../03-security/audit-and-security-event-architecture.md, Section 6](../03-security/audit-and-security-event-architecture.md#6-break-glass-auditing) |
| Sequence gaps | A missing expected event in a known sequence (e.g., certification without a preceding validation event) is detectable |
| Correction behavior | An audit-event correction produces a new event, never an edit to the original |
| Access restrictions | Audit-event access itself is restricted and that restriction is tested |
| Backup and restore continuity | Restored audit data remains complete and correctly ordered |

## 3. Security Testing

Extends [../03-security/security-testing-and-assurance.md, Section 1](../03-security/security-testing-and-assurance.md#1-security-testing-strategy) with the quality-process view:

Static analysis · dependency scanning · secret scanning · authentication tests · session tests · authorization tests · API abuse tests · file-upload tests · webhook tests · Reverb tests · mobile tests · device tests · offline-abuse tests · rate-limit tests · error-disclosure tests · data-exfiltration tests · privileged-access tests · security-event tests · penetration-testing readiness.

Each of these is executed at the test level appropriate to its nature (static analysis in CI, authentication/session/authorization tests at Feature level, API abuse/rate-limit tests at Integration/Contract level, penetration testing as a distinct, separately-scheduled activity per [../03-security/security-testing-and-assurance.md, "Penetration Testing Readiness"](../03-security/security-testing-and-assurance.md#penetration-testing-readiness)) — this document does not re-architect Phase 0.6's security-testing strategy, it operationalizes it within the Phase 0.7 quality process.

## 4. Privacy Testing

| Target | What to Verify |
|---|---|
| Public projection filters | Every public projection contains only Public-tier fields |
| Minor profile visibility | A minor's public profile excludes exact birthdate/full contact info |
| Guardian relationship enforcement | Unverified relationship claims are rejected |
| Medical-data access | Only the minimal clearance-status flag crosses into Eligibility; full detail never does |
| Eligibility evidence access | Evidence documents never surface beyond the review chain |
| Financial export restrictions | Financial exports require the elevated authorization named in [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Finance Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#finance-data-governance) |
| Log redaction | No sensitive field value appears in any log |
| Audit export restrictions | Audit exports require the elevated authorization named in Phase 0.6 |
| Support-access masking | Support-role views are correctly masked/redacted beyond their specific task's need |
| Lower-environment data | No real production personal data exists in any lower environment by default |
| AI data minimization | An AI-feature request includes only the minimum necessary data |
| Offline data minimization | Only the narrow, approved categories replicate to devices |
| Data-sharing restrictions | Every data-sharing instance respects [../03-security/data-sharing-export-and-public-disclosure-controls.md](../03-security/data-sharing-export-and-public-disclosure-controls.md) |
| Retention and disposal workflows | Retention/disposal behavior functions correctly once periods are eventually finalized (tests are structured to be period-agnostic where possible, verifying mechanism rather than a specific hard-coded duration) |

## 5. Compliance Assurance Testing

Without making compliance claims (restated absolutely from working rule 39 — "Distinguish compliance evidence from claims of compliance"), quality engineering verifies:

| Target | What to Verify |
|---|---|
| Control presence | Every control in [../03-security/compliance-control-framework.md, Section 2](../03-security/compliance-control-framework.md#2-control-catalog) has corresponding, executable verification |
| Control ownership | Every control has a named owner category who can produce evidence on request |
| Evidence generation | Test execution produces retrievable evidence (reports, logs) without exposing protected data |
| Policy-source traceability | Where a control cites a policy source, the citation is traceable to [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md) |
| Access reviews | The access-review process (per [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md)) is exercised and produces evidence |
| Retention reviews | Retention-review processes function correctly |
| Disposal evidence | A disposal action correctly produces its required evidence record without retaining the disposed data |
| Incident exercises | Tabletop/incident-response exercises (per [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md)) produce documented outcomes |
| Backup verification | Backup completion/integrity checks function correctly |
| Vendor evidence | Where a vendor is approved, its required evidence (per [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md)) is obtainable |
| Exception tracking | Deliberately-accepted exceptions are tracked with owner and review date, never silently forgotten |
| Audit completeness | The audit trail for a representative business scenario is complete end-to-end |

**This section verifies that PMMS's controls function and produce evidence — it never asserts, and no test result from it should ever be represented as, a claim that PMMS complies with any specific law, regulation, or standard.** That distinction (compliance evidence vs. compliance claim) is load-bearing and must never be blurred in a test report or release document.

## 6. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably static-analysis and dependency/secret-scanning tool selection (mirrors [../03-security/security-open-decisions.md, SD-16](../03-security/security-open-decisions.md#sd-16--static-analysis-security-scanner-selection)) and penetration-testing scheduling (mirrors SD-15).
