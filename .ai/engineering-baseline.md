# Engineering Baseline

Verified 2026-07-18 (WP-01-01..03). Re-verify, don't trust blindly.

## Toolchain
- Laravel Framework 13.19.0, PHP 8.3.16 (satisfies `^8.3`; `composer check-platform-reqs` all pass)
- Node v22.12.0, npm 11.6.1, Composer 2.8.4
- Backend (locked): inertia-laravel 3.1.1, fortify 1.37.2, wayfinder 0.1.20, tinker 3.0.2, pest 4.7.5, larastan 3.10.0, pint 1.29.3
- Frontend (installed): react 19.2.7, @inertiajs/react 3.6.1, typescript 5.9.3, tailwindcss 4.3.2, vite 8.1.4
- `package.json` declares no `engines` field; Node 22.12.0 meets Vite 8 minimum exactly
- npm is the package manager in use (`package-lock.json`); `pnpm-workspace.yaml` exists but no pnpm lockfile

## UI Component Inventory (`resources/js/components/ui/`, 26 files)
alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, icon, input-otp, input, label, navigation-menu, placeholder-pattern, select, separator, sheet, sidebar, skeleton, sonner, spinner, toggle-group, toggle, tooltip

## Backend Quality (WP-01-02)
- Pint (preset laravel): **FAIL** — 12 starter-kit test files, all `single_blank_line_at_eof` only (pre-existing; documented, not fixed per WP scope). *Update: remediated in WP-01-13 — Pint now fully passes.*
- PHPStan/Larastan level 7 (`app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`): **PASS**, 0 errors
- Pest (`php artisan test`, in-memory SQLite): **PASS** — 39 tests, 136 assertions
- Note: `composer run test` chains lint:check first, so it halts at the Pint failure before reaching the suite

## Frontend Quality (WP-01-03)
- ESLint (flat config: js/ts recommended, react + hooks, import order, stylistic; generated wayfinder dirs and `components/ui/*` ignored): **PASS**
- Prettier (`resources/`): **PASS** — all files match
- `tsc --noEmit` (**strict: true**, jsx react-jsx, bundler resolution, `@/*` alias): **PASS**
- Wayfinder-generated `resources/js/{actions,routes,wayfinder}` present locally (gitignored)
- Production build (`npm run build`, Vite 8.1.4): **PASS** — 2296 modules, built in ~20s

## Authentication Baseline (WP-01-04)
- Tests: `tests/Feature/Auth/*` (7 files) + `tests/Feature/Settings/SecurityTest.php`: **PASS** — 30 tests, 110 assertions, 0 failures
- Routes (`php artisan route:list`, 45 total): login/logout, register, forgot/reset-password, email verification (notice/verify/resend), confirm-password, two-factor challenge + enable/disable/QR/recovery-codes/secret-key, passkeys (login/register/confirm/destroy + options), settings (profile/password/security/appearance) — all present
- `config/fortify.php` enabled features: registration, resetPasswords, emailVerification, twoFactorAuthentication (confirm: true, confirmPassword: true), passkeys (confirmPassword: true); `lowercase_usernames: true`
- Custom Fortify actions: `app/Actions/Fortify/CreateNewUser.php`, `ResetUserPassword.php`; provider `app/Providers/FortifyServiceProvider.php`
- Test env uses `BCRYPT_ROUNDS=4` (phpunit.xml only — sane dev default; production default remains 12)
- Nothing modified — verification only

## Environment & Secret Hygiene (WP-01-06)
- Secret check: only `.env.example` is git-tracked (213 tracked files); `.env` and `auth.json` are git-ignored and have **never been committed** (history check clean). `.env.example` contains no secret values (APP_KEY, AWS keys, passwords all empty/placeholder). No Critical finding.
- Local development `.env` runs `DB_CONNECTION=mysql` (database `pmmsdb`, created 2026-07-18; all migrations applied). `.env.example` still ships sqlite defaults for new checkouts:
- Current `.env.example` defaults vs Division Edition target (`.ai/architecture.md`: MySQL is the source of truth):
  - `DB_CONNECTION=sqlite` → target `mysql` (host 127.0.0.1, port 3306, per-environment database name) — the only required change; make it when the first PMMS migration work package runs
  - `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database` — acceptable for an SDO-scale deployment; Redis optional later (REDIS_* vars already present)
  - `BROADCAST_CONNECTION=log`, `MAIL_MAILER=log` — fine for development; revisit only when real-time/mail features arrive
  - `FILESYSTEM_DISK=local` — fine; `config/filesystems.php` s3 disk already supports MinIO/S3-compatible storage via `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT` if ever needed
- `.env.example` deliberately not modified by this work package (defer to the work package that first requires MySQL connectivity)
- `.gitignore` covers `.env`, `.env.backup`, `.env.production`, `/auth.json`, `/storage/*.key` — adequate

## Git Workflow & Repository Governance (WP-01-07)

Conventions (proposed and adopted for Phase 1; no CI file, branch, or commit created by this work package):

- **Branching:** `main` is the default and integration branch. Feature work uses `wp/<id>-<slug>` (e.g., `wp/01-10-file-upload-foundation`) when branching is used; direct work on a dirty `main` working tree is acceptable only while the project is single-developer and pre-release.
- **Commit messages:** imperative subject ≤ 72 chars, prefixed with the work-package ID when applicable — e.g., `WP-01-10: add file upload foundation`. Body explains why when non-obvious. One work package per commit; no drive-by changes.
- **Commit/push discipline:** no commit or push without explicit instruction from the project owner (standing project rule).
- **CI target (future, separately authorized):** a `.github/workflows` pipeline running the full quality gate — `composer run lint:check`, `composer run types:check`, `php artisan test`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `npm run build` — on every push/PR to `main`. Spec reference: `docs/11-backlog/phase-1-quality-gates.md`. Not created here.
- **Branch protection / required status checks:** a future GitHub-settings action once CI exists; not performed here.
- **Pending governance decision (owner):** the working tree carries pre-existing uncommitted deletions of `.claude/skills/*` (starter-kit skill files) and `.github/*` (starter-kit dependabot + lint/tests workflows). Decide whether to commit these deletions (accepting removal) or restore the files. Left untouched by Phase 1 so far.

## Repository State
- Single commit `d84b22d` (initial Laravel 13 React starter kit, Fortify + Inertia v3), branch `main`
- Uncommitted pre-existing deletions: `.claude/skills/*`, `.github/*` (disposition decision documented above)
- Untracked: `.ai/`, `docs/`, plus Phase 1 additions (`README.md` and later work-package files)
- No `mobile/` directory
- No secrets read or recorded
