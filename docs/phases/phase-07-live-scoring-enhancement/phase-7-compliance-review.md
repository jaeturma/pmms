# Phase 7 — Compliance Review (WP-07-03)

**Reviewed:** 2026-07-26 · **Scope:** WP-07-01 through WP-07-02 · **Result: COMPLIANT**
(no Critical, High, or Medium findings; two Low accessibility gaps found and
fixed during this review — see §2)

## 1. Architecture Conformance

| Rule (source) | Status | Evidence |
|---|---|---|
| Modular monolith, avoid unnecessary complexity (`.ai/architecture.md`) | Pass | One new controller (`ScoringSessionController`), two new models (`ScoringSession`, `ScoreEvent`), one new event (`ScoreUpdated`) — no new services, no sport-specific abstractions (deliberately deferred per plan) |
| First new dependency, contained (DESIGN-NOTES) | Pass | `laravel/reverb` (composer) + `laravel-echo`/`pusher-js`/`@laravel/echo-react` (npm) — the project's only dependency addition across 7 phases. `composer audit`: **0 advisories**. `npm audit --omit=dev`: **0 vulnerabilities**. `.env.example` still defaults `BROADCAST_CONNECTION=log` (framework default, unchanged) — a fresh setup gets polling-only live scoring with zero broadcasting infrastructure required |
| MySQL is the source of truth (`.ai/architecture.md`) | Pass | `php artisan migrate:status` → `2026_07_25_140933_create_scoring_sessions_and_score_events_tables` **Ran** on MySQL `pmmsdb` (migration #22) |
| Laravel conventions: validation, policies, services (`.ai/coding-standards.md`) | Pass | Reuses `manage-meet-data`'s underlying `role:admin,organizer` gate for mutations and the exact `Matches — list` authorization logic for viewing — no new gate, no new policy, no new role invented (corrects the discarded draft's "Tournament Manager") |
| React functional components + TypeScript strict (`.ai/coding-standards.md`) | Pass | `scoring/show.tsx` is a typed function component; `tsc --noEmit` strict passes; no `any` introduced |
| Reuse shared components (`docs/component-library.md`) | Pass | `PageHeader`, `EmptyState`, `Badge`, `Button`, `Card`, `Dialog`, `Input`, `Label`, `ConfirmDialog` all reused; no new primitives added |
| UI: responsive, accessible, consistent (`.ai/ui-ux-rules.md`) | Pass (2 gaps fixed) | See §2 |
| **No `EventResult`/`ResultPlacement` write path anywhere in live-scoring code** (DESIGN-NOTES' core rule) | **Pass** | See §3 |
| **`MatchStatus` enum unmodified** | **Pass** | `git log -1 -- app/Enums/MatchStatus.php` → last touched in WP-03-04 (Phase 3); zero diff in the current tree. Live-session state (`ScoringSessionStatus`) lives entirely in the new `scoring_sessions` table, fully decoupled |
| **Reverb absence never breaks the feature** | **Pass** | See §4 — proven by the full test suite running with `BROADCAST_CONNECTION=null` (`phpunit.xml`'s default) end-to-end, plus a dedicated reconnect/concurrency review (§5) |
| Athletes are minors — minimal data, policy-scoped | Pass | `scoring_sessions`/`score_events` carry no athlete/personnel fields at all — only free-text side labels and a running integer score; `board()`'s props expose only `athlete.first_name`/`last_name`/`school.name` for the suggested-label heuristic, same shape already public elsewhere in the manager-only Matches UI |
| No new public exposure this phase | Pass | `grep` of `routes/web.php` confirms all `scoring.*` routes sit inside the `['auth','verified']` group; nothing added to `PortalController` or any guest route |
| Testing rules: full gate before completing a WP (`.ai/testing-rules.md`) | Pass | See §4 |
| One WP at a time, scope only, no commits (`.ai/project-rules.md`) | Pass | 3 WPs executed sequentially on owner instruction (log in `.ai/current-phase.md`); entire phase uncommitted in the working tree awaiting owner instruction |

## 2. Accessibility & Responsiveness (swept this WP)

Checklist matches WP-04-06/WP-05-07, applied to `scoring/show.tsx` (operator
console + read-only live display, including full-screen mode) at phone/
tablet/desktop widths:

- **Fixed (Low):** the bare `+1`/`+2`/`+3` quick-score buttons had no
  accessible name distinguishing which side they scored for — a screen
  reader announced only "+1 button" with no side context. Added
  `aria-label` naming the side and point count (e.g. "Add 1 point, Home").
- **Fixed (Low):** the two-side quick-score control block used a fixed
  `grid-cols-2`, tight enough at a narrow phone width (~320px) to risk the
  three-button row wrapping awkwardly or crowding past its column. Changed
  to stack to one column below `sm:`, and the button rows now wrap
  (`flex-wrap`) instead of assuming they always fit one line.
- **Added:** the live score grid now carries `aria-live="polite"` +
  `aria-atomic="true"`, so a screen-reader user watching the read-only live
  display (a Delegation Officer or another Organizer on a second device) is
  told when the score changes, not just sighted users following the visual
  update.
- **Verified already sound:** heading order (one `h1` via `PageHeader`, no
  other headings on the page — `CardTitle` is a styled `div`, the same
  convention as every other page in this app, not a real heading-order
  gap); decorative icons already `aria-hidden` (`Maximize2`/`Minimize2`/
  `Play`/`Pause`/`Square`, `EmptyState`'s icon, the matches-index `Radio`
  icon on the "Live" column); the "No live session" empty state; every
  form input (`side-a`/`side-b`, correction delta/reason, period/status)
  already `Label`-associated; full-screen mode reuses the same DOM subtree
  (not a separate rendering path), so nothing loses its accessible name
  when toggled.

No behavior changes — pure presentation/markup; no new Pest tests needed
for this section, consistent with how WP-05-07's accessibility pass was
handled.

## 3. Result-Integrity Boundary (re-verified, DESIGN-NOTES' core rule)

- `grep` across `app/Http/Controllers/ScoringSessionController.php`,
  `app/Models/ScoringSession.php`, `app/Models/ScoreEvent.php`, and
  `app/Events/ScoreUpdated.php` for `EventResult`/`ResultPlacement` finds
  **zero write references** — only doc-comments describing what the code
  deliberately does *not* do.
- `ScoringSessionTest.php`'s `'ending a scoring session never creates or
  touches an EventResult'` asserts `EventResult::count() === 0`,
  `ResultPlacement::count() === 0`, and the match's own `status` stays
  `Scheduled` after an `end` call.
- New this WP: `ResultTest.php`'s `'a match can be finalized with a result
  and no live scoring session was ever started (Phase 7)'` proves the
  *inverse* direction — the pre-existing encode→validate flow
  (`POST /results`) works and produces an `EventResult` with **zero**
  `ScoringSession` rows ever created. Together the two tests prove the two
  systems are provably decoupled in both directions, not just by
  inspection.

## 4. Quality Gate (final run, 2026-07-26)

- Pint: **PASS** (clean, full repo) · PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — **608 tests / 3,020 assertions, 0 failures** (607 at
  WP-07-02 close → +1 this WP, the result-only-flow proof in §3)
- ESLint: **PASS** · Prettier: **PASS** · tsc strict: **PASS**
- `npm run build` (Vite production): **PASS**
- `composer audit`: **0 advisories** · `npm audit --omit=dev`: **0 vulnerabilities**
- `php artisan migrate:status`: scoring migration **Ran** on MySQL `pmmsdb`
- App live at http://pmms.app — HTTP 200

## 5. Reconnection and Concurrent-Operator Behavior

Reviewed per this WP's explicit acceptance criterion ("Reverb-absent
operation verified by test", plus the end-to-end/concurrency scope item):

- **Reverb stopping mid-session:** every mutation persists to
  `scoring_sessions`/`score_events` *before* `ScoreUpdated` is broadcast, and
  `ScoreUpdated` is a queued `ShouldBroadcast` event — a stopped or
  unreachable Reverb server cannot block or fail the write. The 5-second
  `scoring.show` poll (unconditional, not gated on Echo connecting) is what
  actually re-syncs a client that was relying on the socket; it picks up
  the exact same `toLivePayload()` shape on its next tick. This is already
  what `ScoringSessionTest.php`'s full-lifecycle test proves, running
  entirely under `BROADCAST_CONNECTION=null` — the suite default.
- **Concurrent operator tabs:** `score()`'s read-modify-write of
  `score_a`/`score_b` is a plain last-write-wins update, not lock-guarded —
  documented as an accepted tradeoff in `docs/live-scoring.md` rather than
  fixed with row locking, since a live session is provisional by
  definition. Critically, `ScoreEvent::create()` runs unconditionally on
  every request regardless of the running-total race, so **no audit row is
  ever silently dropped** — an operator can always reconstruct the true
  sequence of events from `score_events` even if two racing writes left the
  displayed total momentarily inconsistent with one of them. This satisfies
  the acceptance criterion's "must not silently drop a `score_events` audit
  row" without introducing new locking complexity this phase doesn't need.

## 6. Authorization

- Viewing (`scoring.show`, `scoring.board`) mirrors the `Matches — list`
  row exactly: Admin/Organizer any match, Delegation Officer their own
  delegation's matches only (via `entries.delegation.officers`), Viewer
  forbidden — re-verified by `ScoringSessionTest.php`'s dedicated
  authorization tests (guest redirect, Viewer forbidden, Delegation Officer
  own-match-only vs. another delegation's match forbidden) for both
  endpoints.
- Mutations (`start`/`score`/`period`/`pause`/`resume`/`end`) reuse
  `role:admin,organizer` — the same gate match create/update already use.
  No loosening: `'non-managers cannot start, score, or end a scoring
  session'` parametrizes over both non-manager roles.
- `routes/channels.php`'s `match.{matchId}.scoring` private-channel
  authorization callback implements the identical rule in plain PHP (no
  shared helper with the HTTP controller, but logically identical and
  covered by the controller-level tests plus manual inspection) — no
  separate, looser rule for the socket path.
- `docs/authorization.md` carries the two live-scoring rows added in
  WP-07-01, unchanged since.

## 7. Visual Checkpoints (phase README)

1. **After WP-07-01** — an Organizer starts a live scoring session for a
   scheduled match and the running score updates correctly with no Reverb
   server running. **Demonstrable** (proven by `ScoringSessionTest.php`'s
   full lifecycle test under the `null` broadcast driver).
2. **After WP-07-02** — an Organizer operates a live scoreboard while a
   Delegation Officer or another Organizer watches the score update on a
   second device; full-screen mode works. **Demonstrable** (scoreboard page
   authorization + prop-reflection tests; full-screen uses the standard
   Fullscreen API on the existing DOM subtree, no separate rendering path
   to diverge).
3. **After WP-07-03** — the whole feature is demonstrable end-to-end
   (start → score → correct → end → still requires a normal result
   encoding afterward) with a green quality gate. **Demonstrable** — §3's
   two tests plus §4's green gate. Live interactive browser verification
   (including a phone-width visual pass) was not performed in this session
   — the Chrome browser automation tool was unavailable (extension not
   connected) — so this checkpoint's evidence is test-based and an HTTP 200
   liveness check, the same bar WP-07-01/02 used, not a screenshot walk.
   *Flagged for owner follow-up if a manual phone check is wanted before
   sign-off.*

## 8. Findings and Dispositions

1. **No Critical/High/Medium findings.**
2. **Two Low accessibility gaps found and fixed** during this review (§2)
   — bare quick-score button labels, control-row containment at phone
   width. Both resolved in this WP, no further action.
3. **Phase 7 tree uncommitted**, same as every phase before its own commit
   decision. Per project rules nothing is committed without owner
   instruction; the tree is green. *Open — owner decision.*
4. **Live browser walkthrough not performed this session** (§7, item 3) —
   Chrome extension unavailable. Test-based evidence is strong (authorization,
   lifecycle, and prop-shape are all asserted against the real HTTP/Inertia
   responses), but a sighted manual pass on a phone hasn't happened yet.
   *Open — recommend a quick manual check before treating Phase 7 as fully
   signed off, though not blocking given the test coverage.*
5. **Carried, unchanged priority:** `.env.example` defaults to sqlite (Low,
   deliberate); no CI pipeline (Low, needs authorization).

## 9. Recommendation

Phase 7 — Live Scoring Enhancement is complete and internally consistent:
an Admin or Organizer can run a provisional, generic running score for any
scheduled match — visible in near-real-time when Reverb is running and
correctly falling back to 5-second polling when it isn't — while Phase 3's
encode→validate result-integrity core remains completely untouched and is
now provably decoupled in both directions (§3). Two Low accessibility gaps
were found and fixed during this review; no schema, authorization, or
architectural issues were found. Recommended next: owner review of this
report (optionally with a quick manual phone check per §8.4) and a commit
decision for the Phase 7 tree; then either Phase 8 — Post-Deployment
Support (the renamed former Phase 7) or Phase 6 — Reports, UAT, Deployment,
and Turnover (still needing a real plan written for this codebase before
any WP-06 work, per the existing note in `.ai/current-phase.md`) — owner's
call on which comes next.
