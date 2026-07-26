# UAT Script — Public Guest

No account needed — use a private/incognito browser window (or sign out
first) to be sure nothing is leaking from a logged-in session. Best run
after the Organizer script has published "UAT Test Meet" (its Part A,
step 4) — the live-scoreboard step below is timing-sensitive, see the
note there. Cross-reference: `docs/manuals/public-portal-guide.md`.

## Steps

1. **Home page.** Visit `/`. Confirm both "Sample Provincial Meet" (from
   seed data) and "UAT Test Meet" (once the Organizer has published it)
   are listed, each linking to its own meet page. Confirm no unpublished
   or Draft meet appears (if you have another browser signed in as a
   manager, create a throwaway Draft meet there and confirm it never
   shows up here).
   **Expected:** only published meets listed; no sign-in prompt anywhere
   on this page.
2. **Meet page.** Open "UAT Test Meet." Confirm the schedule for its day
   (the slot the Organizer scheduled) shows grouped by venue, with a
   venue guide below (name + address only — no internal notes, even
   though the Organizer may have entered some).
   **Expected:** matches the schedule slot created in the Organizer
   script exactly; no venue notes visible even if the venue itself has
   some internally.
3. **Live scoreboard, while it's running (timing-sensitive).** If you can
   coordinate with the Organizer tester to check this **during** their
   script's live-scoring step (before they click End), open the meet
   page and confirm a **"Live now"** section appears, linking to the
   match's scoreboard — open it and confirm the running score updates
   automatically every few seconds, with a visible **"Live score —
   provisional, not the official result"** badge and absolutely no
   operator controls (no score buttons, no Start/Correct/End, nothing
   editable).
   **Expected:** read-only live score, clearly marked provisional.
4. **Live scoreboard, after it's ended.** If you couldn't time step 3,
   or want to also check the "after" state: confirm the meet page's
   "Live now" section no longer lists this match once the Organizer has
   clicked End — an ended session isn't "live" anymore, even though the
   match itself still exists. If you kept the direct scoreboard URL from
   earlier, confirm it still loads and shows the final score, still
   read-only.
   **Expected:** the match disappears from "Live now" the moment the
   session ends; a saved direct link still works and still shows
   read-only data.
5. **Results.** Once the Organizer has validated the 100m dash result,
   open `/meets/{meet}/results`. Confirm rank, both athletes' names and
   schools, and their marks are shown — and confirm nothing else about
   either athlete (no birthdate, LRN, grade, contact info, photo)
   appears anywhere on this page or its HTML source.
   **Expected:** name + school + mark only, exactly per
   `docs/public-portal.md`'s privacy baseline.
6. **Medal tally.** Open `/meets/{meet}/tally`. Confirm the municipality
   standing appears first (the official verdict) with the school-level
   breakdown below it labeled as reference only.
7. **Announcements.** Once the Organizer has published one for "UAT Test
   Meet," confirm it appears both on the meet page and on the portal
   home page's latest-five list (with the meet name attached on the home
   page's version).
8. **Confirm nothing requires signing in.** Repeat steps 1–7 with your
   browser's dev tools network tab open (or just note it) — confirm no
   request anywhere in this script ever redirects to `/login`.
   **Expected:** the entire portal works with zero authentication at any
   point.
9. **Confirm an unpublished/nonexistent meet 404s cleanly.** Visit
   `/meets/999999` (or any ID you know is unpublished/Draft).
   **Expected:** the app's own styled "not found" page, not a raw
   framework error — with a way back to the portal home, not a dead end.

## See also

`docs/manuals/public-portal-guide.md`, `docs/public-portal.md`,
`docs/live-scoring.md`.
