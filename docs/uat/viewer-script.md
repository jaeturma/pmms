# UAT Script — Viewer

Sign in as the Viewer test account. Best run last, after both the
Organizer and Delegation Officer scripts have finished (so there's real
data — the seeded demo plus "UAT Test Meet" — to verify against). Cross-
reference: `docs/manuals/viewer-manual.md`.

This script is read-only by nature: every step either confirms something
is visible, or confirms it correctly **isn't**.

## Steps

1. **Sign in and check the sidebar.** Confirm every module still appears
   in the sidebar (Athletes, Personnel, Entries, Eligibility, Matches,
   Protests included) — Viewer doesn't get a trimmed sidebar, per
   `docs/manuals/viewer-manual.md`'s opening note.
   **Expected:** full sidebar, no Administrator-only or manager-only
   groups (no "Audit log," "Division settings," "Management,"
   "Incidents," "Announcements").
2. **Dashboard.** Confirm today's schedule and medal tally top five show
   for "UAT Test Meet" (it's Active from the Organizer script), with no
   operational-queue cards and no "your delegation's protests" widget.
   **Expected:** matches the Viewer row of `docs/dashboard.md`'s widget
   table exactly.
3. **Registries.** Sidebar → Municipalities, Schools, Sports, Events,
   Meets, Venues — confirm each list loads with no add/edit/archive
   controls visible anywhere.
   **Expected:** read-only tables throughout.
4. **Schedule.** Sidebar → Schedule. Confirm the slot the Organizer
   created is visible, filterable by meet/venue/day, with no
   add/edit/delete controls.
5. **Delegations.** Sidebar → Delegations. Confirm you can see **every**
   delegation across every meet (not just one), including "UAT Test
   Meet"'s — with no Register/Approve/Officers controls, and no
   Roster/IDs links (those need Delegation Officer or manager access).
   **Expected:** full read-only list.
6. **Forbidden modules — confirm they actually 403.** Click through to
   each of: Athletes, Personnel, Entries, Eligibility, Matches, Protests.
   **Expected:** every one of these shows the app's permission-denied
   page, not the module's real content and not a raw error.
7. **Results.** Sidebar → Results. Confirm the "UAT Test Meet" 100m dash
   result is visible (it's validated) with correct placements, and that
   no Encode/Edit/Validate/Correct controls appear anywhere.
   **Expected:** validated results only, fully read-only.
8. **Medal tally.** Sidebar → Medal tally. Confirm the same
   municipality/school breakdown the Organizer and Officer scripts
   already verified, filterable by meet/sport.
9. **Reports available to you.** Open each of: School participation
   summary (`/reports/participation`), Official result sheet for the
   validated 100m dash result, Medal tally report, Daily schedule sheet
   — confirm each renders with working Print/Download buttons.
   **Expected:** all four work.
10. **Reports NOT available to you.** Try navigating directly to
    `/reports/delegations/{id}/roster` (use the delegation ID from step
    5) and `/reports/events/{id}/entries` — you won't have a link to
    click, so type the URL directly.
    **Expected:** both 403.
11. **Public portal access.** Sidebar has no explicit "public portal"
    link, but confirm visiting `/` while signed in still works and shows
    the same guest-facing pages `docs/uat/public-guest-script.md` covers
    — being signed in as Viewer doesn't unlock anything extra there.

## See also

`docs/manuals/viewer-manual.md`, `docs/authorization.md` (the complete
matrix this script is drawn from).
