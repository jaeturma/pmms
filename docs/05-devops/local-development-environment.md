# PMMS Local Development Environment

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [environment-architecture.md](environment-architecture.md) · [containerization-and-docker-adoption-roadmap.md](containerization-and-docker-adoption-roadmap.md)

This document defines local-development-environment requirements. **No tooling is installed or finalized without repository evidence** — every recommendation below is checked against what this repository already demonstrably uses.

---

## 1. Confirmed Requirements (From Repository Evidence)

| Requirement | Evidence |
|---|---|
| PHP | `^8.3`, per `composer.json` |
| Composer | Standard PHP dependency manager, `composer.json`/`composer.lock` present |
| Laravel | `^13.17`, per `composer.json` |
| Node.js and package manager | `package.json`/`package-lock.json`(-equivalent) present; Vite-based build (`vite`, `laravel-vite-plugin`) |
| MySQL | Confirmed direction per every prior phase; `config/database.php` present with MySQL as a configured connection option (SQLite used only for the `phpunit.xml` test environment) |
| Redis | `REDIS_CLIENT=phpredis`, per `.env.example` |
| Testing | Pest 4 (`pestphp/pest`), Larastan, Pint — all present in `composer.json` |
| Frontend tooling | ESLint, Prettier, TypeScript — all present in `package.json` |
| Flutter and Dart | **Not yet present** — `mobile/` does not exist in this repository |
| MinIO | **Not yet configured** — `config/filesystems.php`'s `s3` disk is present but unconfigured (generic AWS-style env vars, no MinIO-specific endpoint set) |
| Reverb | Confirmed technology direction; not yet installed as a Composer dependency |
| Horizon | Confirmed technology direction; not yet installed as a Composer dependency |

## 2. Local Development Requirements

| Area | Direction |
|---|---|
| Supported operating systems | Windows, macOS, and Linux all supported in principle — this repository's current development environment (confirmed via this phase's own working context) is Windows-based, informing near-term tooling priorities without excluding others |
| PHP version | 8.3+, matching `composer.json` |
| Composer | Standard, no custom registry currently configured |
| Node.js and package manager | Matching `package.json`'s engine expectations (not currently pinned — a candidate future addition) |
| Flutter and Dart | To be established once `mobile/` is scaffolded |
| MySQL | A local MySQL instance (or Sail/Docker-based equivalent, Section 3) matching Production's major version |
| Redis | A local Redis instance, matching the `phpredis` client already configured |
| MinIO | A local MinIO instance or equivalent S3-compatible local storage for realistic object-storage development |
| Mail testing | A local mail-capture tool (e.g., Laravel's `log`/`array` mail driver, already the `.env.example` default, or a dedicated mail-catcher) — never a real mail provider in Local |
| Queue execution | `php artisan queue:listen`, already part of the existing `composer.json` `dev` script (`concurrently` running server, queue listener, and Vite) |
| Reverb | A local Reverb process once installed, for real-time feature development |
| Scheduler | `php artisan schedule:work` or equivalent for local scheduled-task development |
| Local HTTPS readiness | A candidate for `laravel-vite-plugin`'s HTTPS support or a local reverse-proxy tool, evaluated once needed (e.g., for testing secure-cookie behavior) |
| Environment bootstrap | The existing `composer.json` `setup` script (`.env` copy, `key:generate`, `migrate`, `npm install`, `npm run build`) is the confirmed foundation, extended as PMMS domain setup steps are added |
| Sample data | Synthetic seed data, per [../04-quality/test-data-fixture-and-scenario-strategy.md](../04-quality/test-data-fixture-and-scenario-strategy.md) — no seeder is created in this phase |
| Test execution | `php artisan test` / Pest, already wired via the existing `composer.json` `test` script |
| Static analysis | Larastan (`phpstan analyse`), already wired via `types:check` |
| Formatting | Pint (PHP) and Prettier (TypeScript), already wired via `lint`/`format` scripts |
| Log visibility | Laravel Pail (`laravel/pail`, already present in `composer.json`) for real-time local log tailing |

## 3. Local Development Tooling Evaluation

| Option | Assessment |
|---|---|
| Native local development | The current, confirmed approach — PHP/Composer/Node installed directly on the developer machine, matching the existing `composer.json` `dev`/`setup` scripts exactly |
| Laravel Sail or Docker-based local development later | A candidate future addition once Docker adoption progresses (Section, [containerization-and-docker-adoption-roadmap.md](containerization-and-docker-adoption-roadmap.md)) — not adopted now, since no Docker configuration exists in this repository |
| Windows and Laragon support | Relevant given the current development context is Windows-based; Laragon (or an equivalent local-server manager) is a reasonable native-development companion tool, not mandated |
| Linux or macOS support | Fully supported in principle via native tooling or, once available, Sail — no evidence yet of a specific team preference beyond the current Windows-based context |

**No tooling choice is finalized without repository evidence** — restated per the phase's own working instruction; the table above reflects what is demonstrably true of this repository today, not a speculative recommendation.

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably whether Sail/Docker-based local development is adopted before or after the first pilot, and local MinIO/Reverb/Horizon setup documentation timing (dependent on those packages actually being installed, which is explicitly out of scope for this documentation-only phase).
