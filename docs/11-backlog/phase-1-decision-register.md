# PMMS Phase 1 Decision Register

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [../10-review/contradiction-and-decision-resolution-register.md](../10-review/contradiction-and-decision-resolution-register.md)

General, backlog-wide open decisions. Work-package-specific decisions (using `DEC-<WORK PACKAGE ID>-<SEQUENCE>` identifiers) live in each work package's own Section 30.

| Decision ID | Question | Options | Recommended Direction | Required Evidence | Blocking Status | Owner | Target Phase |
|---|---|---|---|---|---|---|---|
| DEC-GENERAL-01 | What WCAG conformance target should Phase 1's design-system and Flutter accessibility work (WP-11-12, WP-12-12) build against, pending formal DX-01 resolution? | (a) WCAG 2.1 AA, (b) WCAG 2.2 AA, (c) block until DX-01 resolves | (a) WCAG 2.1 AA, as a provisional, re-verifiable target | Formal DX-01 decision | Non-blocking — work proceeds provisionally | UX reviewer | Phase 1, revisited at WP-15-05 |
| DEC-GENERAL-02 | Should local Phase 1 development standardize on SQLite (current repository default) or MySQL from WP-01-01 onward, given MySQL is the approved authoritative store? | (a) SQLite until EPIC-04, then MySQL, (b) MySQL from WP-01-01 | (b) MySQL from WP-01-01 — avoids a database-engine migration mid-foundation and lets WP-15-02's schema review reflect the real target engine throughout | None required — this is an environment-provisioning choice, not an architecture decision | Non-blocking | Engineering lead | Phase 1, WP-01-06 |
| DEC-GENERAL-03 | Should Docker Compose be used to provision MySQL/Redis/MinIO-compatible services for local Phase 1 development, given the approved technology direction names Docker for "a later implementation phase"? | (a) Provision natively per-developer, (b) introduce a minimal Docker Compose file scoped only to dependency services (not the app itself) | (b) minimal Docker Compose for dependency services only — consistent with "Docker during a later implementation phase" referring to *application* containerization, not local dependency provisioning | None required | Non-blocking | DevOps lead | Phase 1, WP-01-06 |
| DEC-GENERAL-04 | Should the malware-scanner selection (WP-08-08) be resolved before Phase 1 foundation sign-off (WP-15-12), or deferred to the pilot phase? | (a) Resolve now, (b) defer to pilot | (b) defer to pilot — WP-08-08 is explicitly "readiness" scope; no file-upload-heavy module is in Phase 1 scope yet | Vendor/tooling evaluation | Non-blocking | Security reviewer | Pilot phase |
| DEC-GENERAL-05 | Should EPIC-12 (Flutter) proceed in parallel with Release D, or wait until Release D fully completes, given `mobile/` does not yet exist? | (a) Parallel, starting once WP-01-05/WP-10-01/WP-11-01 are done, (b) strictly sequential after Release D | (a) parallel — EPIC-12's hard dependencies are specific work packages, not entire releases | None required | Non-blocking | Engineering lead | Phase 1, Section 6 of [phase-1-scope-and-release-strategy.md](phase-1-scope-and-release-strategy.md) |

## Summary

5 general decisions registered, none blocking. All five are provisioning/sequencing/interim-target choices that can be revisited without rework risk if the eventual formal decision differs — none commits Phase 1 to an irreversible position on an open Phase 0 architecture decision.
