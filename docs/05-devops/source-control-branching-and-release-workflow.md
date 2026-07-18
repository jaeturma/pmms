# PMMS Source-Control, Branching, and Release Workflow

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/secure-development-lifecycle.md](../03-security/secure-development-lifecycle.md) · [../04-quality/requirements-traceability-model.md](../04-quality/requirements-traceability-model.md)

This document defines source-control workflow, branching model, commit standards, pull-request workflow, and release-versioning direction. **No branch is created, no commit is pushed, and no repository setting is modified by this document**, per working rules 19–20.

---

## 1. Source-Control Workflow

Confirmed repository state (this phase's inspection): a single commit (`d84b22d Initial Laravel 13 installation`) on a single branch (`main`), tracking `remotes/origin/main`. No `.gitattributes`-referenced CI workflow currently exists on disk (a stale `export-ignore` reference to `.github/workflows/browser-tests.yml` was found, from before this session).

| Element | Direction |
|---|---|
| Protected primary branch | `main` is the protected, deployable branch — direct pushes discouraged once a team exists beyond a single contributor |
| Development integration branch if retained | Not currently used; evaluated only if branch-based release trains (Section 5) require one — trunk-based development off `main` is the simpler default for the current team size |
| Feature branches | `feature/<short-description>` or `feature/<work-item-reference>`, short-lived, merged via pull request |
| Fix branches | `fix/<short-description>`, same discipline as feature branches |
| Release branches | Evaluated only if release trains (Section 5) require stabilization windows separate from `main` |
| Hotfix branches | `hotfix/<short-description>`, branched from the deployed release point, merged back to `main` and cherry-picked/re-merged as needed |
| Pull requests | Required for every change reaching `main`, per Section 3 |
| Required reviews | At least one reviewer for ordinary changes; elevated review (Security/Privacy/Domain reviewer, per [../03-security/security-architecture.md, Section 3](../03-security/security-architecture.md#3-security-governance-model)) for changes touching authorization, high-integrity domains, or infrastructure |
| CODEOWNERS readiness | Not currently present in this repository — a candidate future addition once module ownership (per [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md)'s 34-module structure) is established in code |
| Branch protection | A future GitHub-repository-setting configuration (required reviews, required status checks, no force-push to `main`) — not modified by this documentation-only phase |
| Signed commits evaluation | A candidate future integrity control, evaluated once the team and tooling support it — not adopted here |
| Merge strategy | Squash-merge for feature/fix branches (keeping `main`'s history coherent) is the recommended default, pending team confirmation |
| Conflict resolution | Resolved by the branch author before merge, never silently by a reviewer |
| Release tags | Annotated Git tags mark release points, per [incident-problem-change-and-release-management.md, Section 7](incident-problem-change-and-release-management.md#7-release-management) |

**Recommended workflow:** trunk-based development with short-lived feature/fix branches merged to `main` via reviewed pull requests, scaling naturally from the current single-contributor state to a larger team without requiring a workflow change — a GitFlow-style long-lived `develop` branch is evaluated only if release-train cadence (Section 5) later demonstrates a real need for it.

## 2. Commit Standards

1. **Conventional commits readiness** — a `type(scope): description` format (e.g., `feat(eligibility): add reviewer-approver separation check`) is the recommended direction, not yet enforced by tooling.
2. **Clear scope** — a commit's scope names the bounded context or cross-cutting concern it touches.
3. **Work-package reference** — a commit references its originating work item/issue where one exists.
4. **Small, coherent commits** — a commit represents one logical change, not an unrelated bundle.
5. **No secrets** — restated absolutely.
6. **No generated dependency directories** — `vendor/`, `node_modules/`, and build output remain `.gitignore`-excluded, as already confirmed.
7. **No unrelated formatting changes** — a commit doesn't reformat unrelated code alongside its actual change, obscuring the real diff.
8. **No direct production changes** — restated from [../03-security/infrastructure-runtime-and-network-security.md, Section 2](../03-security/infrastructure-runtime-and-network-security.md#2-mysql-security); a commit never represents an ad hoc production edit.
9. **Migration and documentation coupling** — a commit introducing a schema change includes its corresponding documentation update in the same or an immediately-linked commit, never left to drift.
10. **Breaking-change indication** — a commit introducing a breaking API/schema/contract change is clearly flagged as such.
11. **Revert strategy** — a problematic commit is reverted via `git revert` (preserving history), not force-pushed away.

## 3. Pull-Request Workflow

Every pull request includes:

Work-package reference · summary · changed areas (which bounded context(s)/modules) · architecture impact · security impact · privacy impact · database impact · tests (per [../04-quality/](../04-quality/)'s test-level expectations) · evidence · screenshots where relevant (UI changes) · migration and rollback notes · operational impact · documentation changes · known risks · reviewer assignments.

This extends, and does not duplicate, [../04-quality/requirements-traceability-model.md, Section 6](../04-quality/requirements-traceability-model.md#6-definition-of-done) — a pull request is where Definition of Done evidence is actually presented for review.

## 4. Release Versioning

| Versioning Target | Evaluation |
|---|---|
| Semantic versioning (`MAJOR.MINOR.PATCH`) | Recommended for the overall PMMS application release — breaking API/contract changes increment MAJOR, new backward-compatible capability increments MINOR, fixes increment PATCH |
| Calendar versioning | Not recommended as the primary scheme — PMMS's release cadence is more naturally tied to meet cycles and feature readiness than a calendar date, per [../04-quality/quality-open-decisions.md, QD-22](../04-quality/quality-open-decisions.md#qd-22--release-cadence) |
| Product release numbers | The semantic version itself serves this purpose; a marketing/product-facing name (if desired) is a separate, non-technical label layered on top |
| Mobile application build numbers | Follow platform convention (Android version code / iOS build number) independently of the backend's semantic version, since app-store release cadence differs from backend deployment cadence |
| API versioning | Per [../04-quality/api-contract-and-integration-testing.md, Section 2](../04-quality/api-contract-and-integration-testing.md#2-contract-testing) — a distinct concern from application release versioning; an API version changes only when its contract changes |
| Database schema version | Tracked via Laravel's own migration history (once physical schema exists) — not a separately-numbered scheme |
| Rule-set version | Restated absolutely from [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession) — **business rule versions are kept independent from application release versions**; a sports rule-set update is never conflated with a software release number |
| Document-template version | Report/certificate template versions (per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession)) are likewise independent of application release versioning |

**Recommended initial direction:** semantic versioning for the application release artifact, with API contracts, rule-sets, and document templates each maintaining their own independent version identity — never collapsed into a single number.

## 5. Release Trains

A release train (a scheduled, predictable release cadence) is a candidate future practice once the team and pilot activity justify a predictable rhythm — not adopted as a specific cadence in this phase, given the current single-commit repository state. See [../04-quality/quality-open-decisions.md, QD-22](../04-quality/quality-open-decisions.md#qd-22--release-cadence) for the closely related release-cadence question this depends on.

## 6. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably branch-protection rule specifics, CODEOWNERS adoption timing, and whether release trains are adopted before or after the first pilot.
