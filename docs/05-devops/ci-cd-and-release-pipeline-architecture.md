# PMMS CI/CD and Release Pipeline Architecture

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../04-quality/automation-ci-and-quality-gates.md](../04-quality/automation-ci-and-quality-gates.md) · [../03-security/security-metrics-monitoring-and-reporting.md, Section 4](../03-security/security-metrics-monitoring-and-reporting.md#4-release-security-gates) · [database-migration-and-release-safety.md](database-migration-and-release-safety.md)

This document defines continuous-integration, continuous-delivery, and continuous-deployment-boundary architecture, extending [../04-quality/automation-ci-and-quality-gates.md](../04-quality/automation-ci-and-quality-gates.md) (Phase 0.7) with DevOps-operational pipeline detail. **No GitHub Actions workflow or other CI configuration is created here**, per working rule 10.

---

## 1. Continuous Integration Architecture

Conceptual pipeline stages:

```text
Repository Validation
Backend Quality
Frontend Quality
Flutter Quality
Unit and Domain Tests
Feature Tests
Authorization and Security Tests
Integration Tests
Build
Artifact Publication
```

| Stage | Content |
|---|---|
| Repository Validation | `.editorconfig`/`.gitattributes` compliance, commit-message format (once conventional commits is enforced) |
| Backend Quality | Pint, Larastan — already wired via `composer.json` |
| Frontend Quality | ESLint, Prettier, TypeScript checks — already wired via `package.json` |
| Flutter Quality | `flutter analyze`, formatting — once `mobile/` exists |
| Unit and Domain Tests | Per [../04-quality/domain-and-application-testing.md](../04-quality/domain-and-application-testing.md) |
| Feature Tests | Per [../04-quality/laravel-inertia-react-testing.md, Section 1](../04-quality/laravel-inertia-react-testing.md#1-laravel-feature-testing) |
| Authorization and Security Tests | Per [../04-quality/security-privacy-audit-and-compliance-assurance.md, Sections 1 and 3](../04-quality/security-privacy-audit-and-compliance-assurance.md#1-authorization-testing) |
| Integration Tests | Per [../04-quality/api-contract-and-integration-testing.md, Section 3](../04-quality/api-contract-and-integration-testing.md#3-integration-testing) |
| Build | Per [build-artifact-and-dependency-management.md, Section 1](build-artifact-and-dependency-management.md#1-build-architecture) |
| Artifact Publication | Per [build-artifact-and-dependency-management.md, Section 2](build-artifact-and-dependency-management.md#2-artifact-architecture) |

### Optional Scheduled Stages

Full regression (per [../04-quality/regression-smoke-exploratory-and-uat-strategy.md, Section 1](../04-quality/regression-smoke-exploratory-and-uat-strategy.md#1-regression-strategy)) · dependency review · secret scanning · static security analysis · accessibility checks · performance smoke tests · integration-environment deployment — run nightly or on a schedule rather than blocking every pull request, given their cost.

**No workflow file is created by this document.**

## 2. CI Quality Gates

Preserved and consolidated from [../04-quality/automation-ci-and-quality-gates.md, Section 2](../04-quality/automation-ci-and-quality-gates.md#2-ci-quality-gates-candidates):

Formatting · static analysis · unit tests · domain tests · feature tests · authorization tests · architecture tests · frontend type checks · frontend tests (pending QD-01) · Flutter analysis (pending `mobile/` scaffolding) · Flutter tests (pending QD-02) · dependency review · secret scanning · build verification · documentation checks · migration review (once physical schema exists).

**Exact thresholds remain open decisions** — restated per the phase's own working instruction; this document names the gate categories, not their enforcement configuration.

## 3. Continuous Delivery Architecture

```text
Merge
Build
Validate
Publish immutable artifact
Deploy to integration
Run automated checks
Promote to QA
Run acceptance checks
Promote to staging or pilot
Approve production release
Deploy
Verify
Monitor
Close release
```

**Promotion uses the same built artifact where practical** — restated from [devops-and-platform-operations-strategy.md, Section 2](devops-and-platform-operations-strategy.md#2-devops-principles) (Principle 4); an artifact is built once and promoted through environments, never rebuilt per environment, reducing the risk of environment-specific build drift.

## 4. Continuous Deployment Boundaries

| Boundary | Direction |
|---|---|
| Automatic deployment for lower environments | May be acceptable — Automated Test, Integration, and (with review) Shared Development/QA are reasonable candidates for automatic deployment on merge |
| Production deployment | Should initially require explicit approval — restated per the phase's own working instruction; not fully automatic from the outset |
| Meet-day production deployment | May require additional restrictions beyond ordinary production deployment, per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md) — restated from working rule 45 |
| Database changes | Require approval regardless of environment automation level, per [database-migration-and-release-safety.md](database-migration-and-release-safety.md) |
| High-risk security and privacy changes | Require Security/Privacy reviewer review before deployment, restated from [../03-security/secure-development-lifecycle.md, Section 1](../03-security/secure-development-lifecycle.md#1-secure-development-lifecycle-phases) |
| Mobile releases | Depend on application-store or distribution constraints (review time, staged rollout mechanisms) outside PMMS's own pipeline control |
| Device software | May need staged rollout across the device fleet rather than an all-at-once update, per [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md) |
| Emergency hotfixes | Still require the minimum viable gates (tests, security check) and a mandatory post-deployment review — never a fully-ungated emergency path |

## 5. Security and Privacy Pipeline Gates

Candidate checks, extending [../03-security/secure-development-lifecycle.md](../03-security/secure-development-lifecycle.md) and [../04-quality/security-privacy-audit-and-compliance-assurance.md](../04-quality/security-privacy-audit-and-compliance-assurance.md) into the pipeline specifically:

Secret scanning · dependency-vulnerability checks · static analysis · authorization tests · privacy-filter tests · sensitive-prop checks (per [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security)) · file-upload tests · audit-coverage checks · infrastructure scanning later · container scanning later · license checks later · policy-source checks for rule changes (confirming a sports/eligibility-rule change cites [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)) · protected-data checks in fixtures (confirming no real minor/medical/eligibility/guardian/finance/auth/audit data appears in any committed fixture).

## 6. Database Migration Gates

Restated and extended from [database-migration-and-release-safety.md](database-migration-and-release-safety.md) — a migration requires: review, forward compatibility, backward compatibility where needed, data-volume assessment, locking assessment, index impact, backup readiness, rollback-or-forward-fix plan, data-migration verification, a production-execution-time estimate based on Staging evidence, an application-compatibility window, post-deployment verification, and documentation. **Do not assume every migration can be rolled back safely** — restated absolutely per working rule 43.

## 7. Frontend and Flutter Build Gates

| Gate | Direction |
|---|---|
| Frontend build gate | The Vite production build must succeed with no TypeScript errors, no ESLint errors, and (once adopted) a passing frontend test suite before an artifact is published |
| Flutter build gate | Once `mobile/` exists: `flutter analyze` and `flutter test` must pass, and the release build (APK/AAB, and IPA if approved) must complete successfully |

## 8. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably CI platform selection (mirrors [../04-quality/quality-open-decisions.md, QD-03](../04-quality/quality-open-decisions.md#qd-03--ci-platform-and-pipeline-configuration)), and the specific promotion-approval workflow for Staging → Production.
