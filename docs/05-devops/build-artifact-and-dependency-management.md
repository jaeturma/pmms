# PMMS Build, Artifact, and Dependency Management

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/secure-development-lifecycle.md, Section 4](../03-security/secure-development-lifecycle.md#4-dependency-and-supply-chain-security) · [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md)

This document defines the build-stage architecture, candidate release artifacts, and dependency-management operations. **No build script, artifact, or CI configuration is created here.**

---

## 1. Build Architecture

Conceptual build stages:

1. **Checkout** — retrieve the exact source revision being built.
2. **Dependency resolution** — `composer install`/`npm ci` (or Flutter package resolution, once `mobile/` exists) against committed lock files.
3. **Dependency integrity check** — lock-file hashes are verified, not merely assumed current.
4. **Backend validation** — Pint formatting check, Larastan static analysis (both already wired via existing `composer.json` scripts).
5. **Frontend validation** — ESLint, Prettier, TypeScript checks (already wired via existing `package.json` scripts).
6. **Flutter validation** — `flutter analyze` and formatting, once `mobile/` exists.
7. **Static analysis** — restated/consolidated from stages 4–6, plus any future security-focused static analysis (per [../03-security/security-open-decisions.md, SD-16](../03-security/security-open-decisions.md#sd-16--static-analysis-security-scanner-selection)).
8. **Tests** — the full Pest suite (`php artisan test`, already wired) plus, once adopted, the frontend/Flutter test suites (per [../04-quality/quality-open-decisions.md, QD-01/QD-02](../04-quality/quality-open-decisions.md#qd-01--frontend-test-framework-selection)).
9. **Security checks** — secret scanning, dependency-vulnerability scanning (Section 3).
10. **Asset build** — `npm run build` (Vite), already the confirmed frontend build mechanism.
11. **Artifact assembly** — Section 2.
12. **Metadata generation** — version, commit SHA, build timestamp attached to the artifact.
13. **Signing or integrity verification readiness** — a candidate future control (checksums at minimum, cryptographic signing evaluated), not committed to a specific mechanism.
14. **Publication** — the artifact is published to a candidate artifact store (Section 2), not yet selected.

**Builds must be reproducible** — restated absolutely; the same source revision and lock-file state produce the same build output, every time, on any build agent.

## 2. Artifact Architecture

| Candidate Artifact | Contents |
|---|---|
| Laravel application release artifact | Application code, vendored dependencies (or a dependency-resolution manifest), compiled configuration cache readiness |
| Compiled React assets | Vite build output (`public/build/`, already `.gitignore`-excluded from source, produced fresh per build) |
| Flutter Android package | An APK/AAB, once `mobile/` exists and a release pipeline is established |
| Future iOS package if supported | An IPA, contingent on iOS support being approved (not yet decided) |
| Container images later | Per [containerization-and-docker-adoption-roadmap.md](containerization-and-docker-adoption-roadmap.md) — not created in this phase |
| Database migration package | The set of pending migrations for a given release, reviewed per [database-migration-and-release-safety.md](database-migration-and-release-safety.md) |
| Generated API specification | An OpenAPI (or equivalent) document, once the API surface is implemented — not yet generated |
| Release notes | Per [incident-problem-change-and-release-management.md, Section 7](incident-problem-change-and-release-management.md#7-release-management) |
| Software bill of materials readiness | A candidate future artifact (a full dependency manifest with versions/licenses) — not yet generated |
| Checksums | A hash of every artifact, supporting integrity verification |
| Documentation bundle | The relevant subset of `docs/` reflecting the release's state |
| Deployment manifest | A description of what changed and what deployment steps are required, per [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md) |

Every artifact carries: version, commit SHA, build time, an environment-independent configuration expectation (the artifact itself makes no environment-specific assumption — configuration is injected at deploy time, never baked in), dependency lock references, and integrity metadata (checksum at minimum).

## 3. Dependency Management

| Dependency Category | Current State (This Repository) |
|---|---|
| Composer | `composer.json`/`composer.lock` present; Laravel 13, Fortify, Inertia, Wayfinder, Pest 4, Larastan, Pint — all well-established, actively-maintained packages, confirmed during Phase 0.6's inspection |
| npm | `package.json` present; React 19, Inertia, Tailwind 4, Radix-based shadcn/ui components, Vite — no test-framework dependency yet (QD-01) |
| Flutter packages | Not yet applicable — `mobile/` does not exist |
| GitHub Actions later | No workflow currently exists; any future action reference is pinned, never `@main`/`@latest`, per [../03-security/secure-development-lifecycle.md, Section 4](../03-security/secure-development-lifecycle.md#4-dependency-and-supply-chain-security) |
| Container base images later | Not applicable until Docker adoption progresses |
| Operating-system packages | A future infrastructure-phase concern |
| Third-party SDKs | None currently integrated — no external vendor is approved, per [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors) |

### Requirements

- **Lock files** — `composer.lock` and the npm lock file are committed and respected, restated as required (already the confirmed practice).
- **Approved update process** — dependency updates follow a reviewed, scheduled process (Section, [patch-vulnerability-and-dependency-operations.md](patch-vulnerability-and-dependency-operations.md)), not ad hoc.
- **Vulnerability review** — every dependency update is checked against known vulnerabilities before adoption.
- **Compatibility testing** — a dependency update runs the full test suite before being merged.
- **License-review readiness** — a candidate future process, not yet implemented.
- **Deprecated package removal** — an unmaintained dependency is a tracked candidate for replacement.
- **Emergency patch process** — a security-critical dependency vulnerability has an expedited update path, distinct from routine scheduled updates.
- **Reproducible installation** — `composer install`/`npm ci` (not `composer update`/`npm install`) is used in build pipelines, ensuring the lock file — not a potentially-drifted resolution — governs what's actually installed.

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably artifact-store/registry selection and whether a software-bill-of-materials tool is adopted before the first pilot release.
