# PMMS — Provincial Meet Management System (Division Edition)

Management system for provincial athletic meets, built for a DepEd Schools Division Office.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3), Fortify authentication |
| Frontend | React 19 + Inertia.js v3 + TypeScript (strict) |
| Styling | Tailwind CSS 4, shadcn/ui-style components (`resources/js/components/ui/`) |
| Build | Vite 8, Wayfinder (typed routes) |
| Database | MySQL (source of truth); SQLite in-memory for tests |
| Testing | Pest 4 (backend) |
| Quality | Larastan (PHPStan level 7), Pint, ESLint, Prettier |

The verified toolchain baseline lives in [`.ai/engineering-baseline.md`](.ai/engineering-baseline.md).

## Requirements

- PHP 8.3+ with Composer 2
- Node.js 22.12+ with npm
- MySQL 8 (local development) — tests run on in-memory SQLite and need no database server

## Setup

```bash
composer run setup
```

This installs Composer and npm dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, and builds assets. Configure your MySQL credentials in `.env` before running migrations. Never commit `.env` or any secret.

## Development

```bash
composer run dev     # serve + queue listener + Vite dev server, concurrently
```

## Quality Checks

Run all of these before completing any work package (see `.ai/testing-rules.md`):

```bash
composer run lint:check    # Pint (PHP formatting) — composer run lint to auto-fix
composer run types:check   # Larastan/PHPStan level 7
php artisan test           # Pest test suite
npm run lint:check         # ESLint — npm run lint to auto-fix
npm run format:check       # Prettier — npm run format to auto-fix
npm run types:check        # tsc --noEmit (strict)
npm run build              # production build must succeed
```

`composer run test` chains Pint + PHPStan + Pest; `composer run ci:check` adds the frontend checks.

## Project Structure

```text
app/                  Laravel application code (models, controllers, actions, providers)
resources/js/         React + Inertia frontend (pages/, components/, layouts/, hooks/, lib/)
resources/js/components/ui/   Reusable UI primitives (shadcn/ui-style)
routes/               web.php, settings.php, console.php
database/             migrations, factories, seeders
tests/                Pest tests (Feature/, Unit/)
docs/                 project documentation and phase work packages
.ai/                  AI-assisted development workspace (rules, current phase, baseline)
```

## Development Workflow

- Work proceeds one work package at a time — see [`.ai/current-phase.md`](.ai/current-phase.md) and `docs/phases/`.
- Inspect the repository before coding; implement only the active work package's scope.
- Follow [`.ai/coding-standards.md`](.ai/coding-standards.md) and [`.ai/project-rules.md`](.ai/project-rules.md).
- Keep the system simple and maintainable — avoid enterprise-level complexity unless required.
- Do not commit or push unless explicitly instructed.
