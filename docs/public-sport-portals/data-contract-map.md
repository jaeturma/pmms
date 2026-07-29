# Public Sport Portals — Data Contract Map

The full, section-by-section field mapping (existing field / missing field /
formatting-only / honest-empty-state) lives in this phase's own planning
document and is not duplicated here:

`docs/phases/phase-12-lightweight-sport-mini-portals/DATA-CONTRACT-MAP.md`

That document (Step 2 of the original brief's own workflow) covers, against
real verified source:

- **A. Routing/meet resolution** — `/{sportSlug}` → `Sport` + active
  published meet.
- **B. Live Now** — featured live match, score/clock/sport-specific state,
  venue/stage, "other live" count, next-scheduled fallback.
- **C. Today's/Completed/Upcoming Games** — schedule, competitors, venue,
  category, status, score, derived winner, the 10-item cap.
- **D. Standings** — confirmed no backing data anywhere; honest
  "not available yet" for every sport.
- **E. Leading Scorers** — confirmed no backing data anywhere; honest
  "not available yet" for every sport.
- **F. Tournament Bracket** — confirmed no backing data anywhere; honest
  "not available yet" for every sport (a flat round-robin list was
  considered as a fallback and not built, since no round-robin sport
  currently has real match data to summarize this way).
- **G. Venue Information** — name/address, current/next event at that
  venue, a generated map-search link.
- **H. Sport configuration** — `SportPortalConfig` shape and how each field
  is sourced (`resources/js/config/sport-portals.ts`).

The companion `INSPECTION-REPORT.md` in the same directory documents the
underlying schema findings (no `match_id` on `EventResult`, side-level-only
`ScoreEvent`s, free-text `round_label`) that this map's D/E/F rows are based
on. Nothing in the actual implementation (WP-12-02 through WP-12-07) altered
any row in that map — re-confirmed while writing this WP by re-reading the
current `sportPortalData()`/`individualEventSportPortalData()` methods
against it field-by-field.
