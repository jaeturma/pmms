# Phase 4 Design Notes

Correction to the superseded draft: **delegations in the Division Edition are per
school** (municipality-based delegations were never built); the medal tally
aggregates by school and rolls up to districts, exactly as the internal tally
does.

Important rules:

- MySQL is the source of truth; the portal reads the same tables operations write.
- Nothing about a meet is public until a manager publishes it — an explicit,
  audited, reversible decision (`meet.published` / `meet.unpublished`).
- Results reach the public only when **validated** (WP-03-05 flow). A correction
  that reopens a result removes it from the portal automatically, the same way it
  leaves the tally.
- Public pages must exclude protected data: no birthdates, LRN, grade levels,
  photos, contact details, eligibility material, protests, incidents, or audit
  data. Athlete identity on the portal is name + school + placement, full stop.
- Public controllers build dedicated, minimal prop arrays. Never pass an internal
  page's props to a public page.
- Public routes are guest routes (no auth, no Fortify middleware) on a dedicated
  public layout; the internal app keeps its own layout untouched.
- The portal must work well on mobile browsers first — meet-day traffic is
  phones on venue Wi-Fi. Server-rendered Inertia pages, no client-side data
  fetching, no websockets; refresh is the update model at MVP.
- Announcements are the only new entity in the phase; everything else is
  read-side projection of existing data.
