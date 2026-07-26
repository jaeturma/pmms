# Deployment (WP-06-07)

The single reference for building, deploying, restarting, and rolling
back PMMS Division Edition in its actual production topology: **this same
local Laragon machine** (`http://pmms.app`) — no new cloud/VPS
infrastructure this phase, per owner decision 2026-07-26. Everything below
was chosen for that specific, single-server, single-machine-access
topology; it is not a generic multi-server deployment guide.

## Topology and access (owner-confirmed 2026-07-26)

PMMS is reached from **this one machine only** — used directly at the
machine or via remote access into it, not by other computers on a network
reaching it independently. Concretely: `pmms.app` resolves via this
machine's own hosts file (`127.0.0.1 pmms.app`), which by design only
works on this machine; no other computer's browser could resolve that
hostname even if it were on the same network. If that ever changes (e.g.
other SDO office computers need to reach this server over the LAN), the
access topology and the HTTPS/TLS finding below should both be revisited
— this doc's answers are for "just this one machine," not a general-
purpose deployment.

## Production `.env`

`.env.production.example` (repo root) is the template — placeholder
values only, **never** commit a real `.env`. Copy it to `.env` on the
server and fill in every `REPLACE_…` placeholder before first use. The
values that differ from the dev-oriented `.env.example`, and why:

| Key | Production value | Why |
|---|---|---|
| `APP_ENV` | `production` | Disables debug-mode error pages, enables production framework behaviors. |
| `APP_DEBUG` | `false` | **Never `true` in production** — a `true` value leaks stack traces, env values, and query bindings to anyone who triggers an error. |
| `APP_KEY` | generated, unique to this deployment | Run `php artisan key:generate --show` once and paste the output in — never reuse the dev key. |
| `DB_CONNECTION` / `DB_*` | `mysql`, pointed at `pmmsdb` | Matches the real database this deployment already uses (dev already runs on the same MySQL instance). |
| `SESSION_SECURE_COOKIE` | `true` | **WP-06-03 security finding, carried forward here as instructed** — unset/false by default means session cookies would be sent even over a non-HTTPS connection; `APP_URL` is `https://`, so this must be `true`. |
| `BROADCAST_CONNECTION` | `log` | Owner decision 2026-07-26 — polling-only in production, see "Broadcast decision" below. |
| `MAIL_MAILER` | `smtp` (placeholders) | Owner decision 2026-07-26 — no SMTP provider available yet; see "Outgoing email" below, this is a real to-do, not filled in. |
| `PMMS_ADMIN_PASSWORD` | a real, strong password | `config/pmms.php`'s local-environment fallback (`password`) does not apply outside `local`/`testing` — a blank value here would leave the seeded admin account with **no** usable credential in production, not an insecure default. |
| `LOG_LEVEL` | `error` | Dev's `debug` level is noisy for a production log file; `error` still captures everything worth seeing without every request's routine debug chatter. |

Everything else (`SESSION_DRIVER=database`, `CACHE_STORE=database`,
`QUEUE_CONNECTION=database`) stays as-is — database-backed session/cache/
queue are entirely appropriate at this single-server scale; there is no
need to introduce Redis or another store for this deployment.

## Queue worker (the real functional gap this WP closes)

**The problem:** `QUEUE_CONNECTION=database` means queued jobs sit in the
`jobs` table until something processes them. Nothing did — no scheduled
task or service ran `php artisan queue:work` continuously. The only
queued job in the app today is Phase 7's `App\Events\ScoreUpdated`
(`ShouldBroadcast`, dispatched on every live-scoring score change — see
`docs/live-scoring.md`); without a worker, every one of those events
silently piled up in `jobs` forever and never actually broadcast.
**This was never a functional break for end users** — live scoring's
5-second polling fallback reads directly from `scoring_sessions`,
completely independent of the queue — but the jobs table would grow
unbounded, and any future queued feature would inherit the same silent
gap.

**The fix:** `scripts/install-queue-worker-schedule.ps1` — registers a
Windows scheduled task that runs `php artisan queue:work --tries=3
--backoff=5` at machine startup and restarts it (up to 999 times, 1
minute apart) if it ever stops. Like `install-backup-schedule.ps1`
(`docs/backup-restore.md`), this is **not** run automatically by
anything in this repo — the server administrator runs it once,
deliberately:

```
powershell -File scripts\install-queue-worker-schedule.ps1
Start-ScheduledTask -TaskName 'PMMS Queue Worker'
```

**Proven end-to-end this WP, not just configured and assumed to work:**
a real scheduled match and live scoring session were created, a score
change was submitted through the actual `ScoringSessionController::score()`
code path (the same one the real UI hits), confirmed a job landed in the
`jobs` table, then ran `php artisan queue:work --once` (with
`BROADCAST_CONNECTION=log`, matching the production decision below) and
confirmed: the job processed successfully, `jobs` returned to 0 rows,
`failed_jobs` stayed at 0, and `storage/logs/laravel.log` recorded the
actual broadcast payload:

```
[…] local.INFO: Broadcasting [score.updated] on channels [private-match.1.scoring] with payload:
{"session":{"id":1,"match_id":1,"status":"in_progress", … "score_a":1,"score_b":0, …}}
```

The test match/session were deleted immediately afterward — nothing from
this verification was left in the database.

## Broadcast decision: polling-only (owner decision 2026-07-26)

Reverb (real-time WebSocket push) will **not** run in production for this
deployment. `BROADCAST_CONNECTION=log` — every live-scoring update is
still fully readable by every viewer via the existing 5-second poll
(`docs/live-scoring.md`'s documented, already-proven-to-work-standalone
baseline); there is simply no second always-on process (`php artisan
reverb:start`) to supervise. This matches Phase 7's own design principle
that Reverb is strictly additive, never required.

**Changing this later:** if real-time push is wanted, set
`BROADCAST_CONNECTION=reverb` and add the `REVERB_APP_ID`/
`REVERB_APP_KEY`/`REVERB_APP_SECRET`/`REVERB_HOST`/`REVERB_PORT`/
`REVERB_SCHEME` and `VITE_REVERB_*` variables (see `docs/live-scoring.md`
for the exact set — this local dev environment already has a working
example in its own non-production `.env`), then supervise
`php artisan reverb:start` the same way this WP now supervises the queue
worker (a second scheduled task, `AtStartup` + restart-on-failure). The
queue worker stays required either way — Reverb doesn't replace it, it's
an additional consumer of the same queued events.

## Outgoing email — required before go-live, not yet resolved

**This is a real, open to-do, not something this WP invented a fake
answer for.** Fortify's email verification (required before any
self-registered account can do anything beyond viewing its own profile —
see `docs/manuals/admin-manual.md` §2) and the password-reset flow both
need real outgoing mail. `.env.production.example`'s `MAIL_MAILER=smtp`
block has placeholder host/username/password values — as of this WP
(2026-07-26) no SMTP provider was available to configure. Before this
deployment goes live for real users: arrange an SMTP provider (a Gmail/
Workspace SMTP relay, a transactional provider, or a DepEd/SDO mail
server if one exists) and fill in the real `MAIL_HOST`/`MAIL_PORT`/
`MAIL_USERNAME`/`MAIL_PASSWORD` values. Until then, registration and
password reset will not actually deliver their emails in production —
`MAIL_MAILER=log` in the interim is not a substitute for real delivery,
only a way to see what *would* have been sent.

## HTTPS/TLS posture (finding, no action taken)

**Finding:** this deployment is reached only from the machine it runs on
(see "Topology" above) — traffic never leaves the loopback interface, so
TLS provides no meaningful confidentiality/integrity benefit here (there
is no network path to intercept). `APP_URL=https://pmms.app` already
works today via Laragon's own local HTTPS setup (port 443 is listening
and serving), which is sufficient as-is for this exposure level. **No new
TLS configuration was added this WP** — none is needed at this access
level, per the owner-confirmed topology. If access ever expands beyond
this one machine (see "Topology" above), this finding — and
`SESSION_SECURE_COOKIE`'s dependence on `APP_URL` actually being served
over HTTPS from wherever it's reached — should both be reviewed again at
that time, since a real network-exposed deployment would need a properly
trusted certificate, not just a working local one.

## Build / deploy procedure

```
# 1. Pull the release
git pull origin main          # or checkout a specific tested commit/tag

# 2. Backend dependencies (production only, no dev tooling)
composer install --no-dev --optimize-autoloader

# 3. Frontend build — also regenerates Wayfinder's typed route helpers via
#    the Vite plugin; prefer this over a bare `wayfinder:generate` call,
#    which needs the `--with-form` flag or several pre-existing pages
#    silently lose their .form() variant (hit once during Phase 5, see
#    docs/management-dashboard.md).
npm install
npm run build

# 4. Apply the .env (see "Production .env" above) and migrate
php artisan migrate --force

# 5. Cache framework config/routes/views for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart anything holding old cached config/queued-job class definitions
php artisan queue:restart
```

Notes on steps that don't apply here: `php artisan storage:link` is
**not needed** — every file this app serves (athlete/personnel photos,
eligibility documents, file uploads generally) goes through an
authenticated, policy-checked application route on the private `local`
disk (`docs/file-uploads.md`), never the public disk the symlink exposes;
grepping the codebase confirms nothing references `Storage::disk('public')`
anywhere. There is no web-server restart step beyond Laragon's own
Apache/Nginx reload, since this deployment doesn't front the app with a
separate reverse proxy.

**Queue worker after a deploy:** `php artisan queue:restart` (step 6)
signals the running worker to finish its current job and exit cleanly;
the scheduled task from "Queue worker" above then restarts it
automatically within a minute, now running the newly-deployed code. No
manual worker restart is needed if the scheduled task is installed and
running.

## Rollback procedure

Proportionate to this single-server, git-based topology — not a blue-
green or multi-region strategy:

1. **Before deploying**, note the current commit (`git rev-parse HEAD`)
   and, if the release includes migrations, take a fresh database backup
   (`scripts\backup-database.ps1` — see `docs/backup-restore.md`; this is
   the safety net for schema changes that aren't cleanly reversible, not
   `migrate:rollback`).
2. **If a deploy goes wrong**, decide the scope of the problem first:
   - **Code-only issue** (no new migrations ran, or they're safe to
     leave): `git reset --hard <previous-commit>` (or `git checkout
     <previous-tag>`), then re-run steps 2–3 and 5–6 of the build
     procedure above (dependencies, frontend build, cache, queue
     restart) against the reverted code. **Run `git status` first** and
     stash or back up anything uncommitted before a hard reset — see the
     project's own git-safety conventions.
   - **A migration ran and needs undoing too**: prefer restoring the
     pre-deploy backup from step 1 over `php artisan migrate:rollback` —
     this project's migrations aren't written with rollback safety as a
     hard guarantee (some early-phase migrations are additive-only with
     a `down()` that's a plain `dropIfExists`, which is destructive to
     any data written since). Restore via `scripts\restore-database.ps1`
     (refuses to target the live `DB_DATABASE` name without `-Force`, by
     design — read `docs/backup-restore.md` before running it against
     production), then revert the code as above.
3. **After either path**, re-verify the app is actually serving correctly
   (`http://pmms.app` returns 200, sign-in works) before considering the
   rollback complete.

## See also

- `docs/backup-restore.md` — the backup/restore tooling this rollback
  procedure and the migration-rollback caveat both depend on.
- `docs/live-scoring.md` — the full Reverb/broadcast architecture behind
  the queue-worker fix and broadcast decision above.
- `docs/manuals/admin-manual.md` — day-to-day administration once
  deployed; §2's account-creation limitation is directly relevant to
  first go-live (someone needs to be promoted to Organizer before the
  Division can actually run a meet).
- `docs/phases/phase-06-reports-uat-deployment-turnover/phase-6-security-review.md`
  — the WP-06-03 finding `SESSION_SECURE_COOKIE` closes here.
