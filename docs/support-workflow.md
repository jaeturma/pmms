# Bug & Support Workflow

WP-09-01. How a bug report or support request moves from filed to closed,
after go-live. GitHub Issues on this repo (`github.com/jaeturma/pmms`) is
the tracker — not a new in-app feature, not a markdown log this project
maintains by hand.

## Filing an issue

Two templates, matching the two things that actually go wrong in a system
like this — pick whichever fits, at
`github.com/jaeturma/pmms/issues/new/choose`:

- **Bug report** (`.github/ISSUE_TEMPLATE/bug_report.yml`) — something that
  used to work, or should work per `docs/manuals/`, doesn't. Asks for what
  happened, what you expected instead, exact steps to reproduce, which role
  you were signed in as, and any error text/screenshot.
- **Support request** (`.github/ISSUE_TEMPLATE/support_request.yml`) —
  something you need done that the app doesn't self-serve today, most
  commonly a role promotion (see
  [`docs/manuals/admin-manual.md`](manuals/admin-manual.md) §2 for why
  that's a real, documented limitation, not an oversight). Asks what's
  needed, why, and how urgent it is.

Neither template accepts a blank issue — if a question doesn't fit either
category, the templates point at `docs/manuals/` first, since most "how do
I…" questions are already answered there.

## Labels

A small, fixed set — no automation applies these, whoever triages an issue
adds them by hand:

| Label | Meaning |
|---|---|
| `bug` | Filed via the Bug report template |
| `support` | Filed via the Support request template |
| `needs-triage` | Applied automatically by both templates; removed once someone has looked at it and decided what happens next |
| `blocking` | Add this on top of `bug`/`support` if it's stopping an active meet right now — triage it first |

No severity scale beyond `blocking` vs. everything else — this deployment
is one Division running one meet at a time, not a system with enough
concurrent issue volume to need a finer scale. Add one later if that stops
being true.

## Triage → fix → close

1. **Whoever administers the server** (see `docs/turnover.md`'s escalation
   table) reads a newly-filed issue, removes `needs-triage`, and decides:
    - **Real bug, fixable now** — fix it, reference the issue number in the
      commit, close the issue when the fix is deployed
      (`docs/deployment.md`'s procedure).
    - **Real bug or support need, not fixable right now** — leave it open,
      add a short comment on why (waiting on a decision, waiting on the
      next deployment window, etc.).
    - **Support request that's really a role/account change** — the server
      administrator makes the change directly (`docs/authorization.md`'s
      `php artisan tinker` step), then comments and closes.
    - **Not actually a bug** (user error, already-documented behavior) —
      comment with a pointer to the relevant manual section, close.
    - **A feature request or scope change in disguise** — this workflow
      doesn't decide those; re-label or comment that it needs a product
      decision and route it the same way `docs/turnover.md`'s escalation
      table already does for "a request for a new feature or a scope
      change."
2. **Closing**: an issue closes once the fix is live (for a bug) or the
   requested change is made (for a support request) — not before. A
   `blocking` issue should be commented on with an ETA if it can't close
   same-day.

## What this workflow deliberately doesn't do

No GitHub Actions or CI automation, no issue-routing bot, no formal SLA
(who responds by when is a business decision for whoever owns this
deployment, not something this document invents), no automated
notifications beyond GitHub's own default issue-activity emails/
notifications for whoever is watching the repo.

## See also

- `docs/turnover.md` — the escalation table (who to actually contact) and
  routine maintenance checklist this workflow assumes.
- `docs/monitoring.md` — how to tell if something's actually wrong with the
  running app before or instead of waiting for someone to file an issue.
- `docs/manuals/` — role-based usage documentation; most support requests
  are answered here before they need a filed issue at all.
