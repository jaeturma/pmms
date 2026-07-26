# UAT Script — Administrator

Independent of the other scripts — run any time. Sign in as the
Administrator test account (see `README.md` "Environment setup"). Cross-
reference: `docs/manuals/admin-manual.md`.

## Steps

1. **Sign in** at `/login` with the Administrator test account.
   **Expected:** you land on the Dashboard; the sidebar shows an
   Administrator-only "Audit log" and "Division settings" group at the
   bottom, in addition to every other module.
2. **Account settings.** Open your account menu → Settings → Security.
   Set up two-factor authentication (scan the QR code with an
   authenticator app, confirm a code) and note down the recovery codes
   shown. **Expected:** 2FA is now required on your next sign-in; the
   recovery codes are shown once.
3. **Division settings.** Sidebar → Division settings. Confirm the name
   reads "Davao de Oro" and the type is "Province," and that the type
   field is **not editable** (a note explains it's locked once any
   delegation exists — the seeded sample data already has two).
   **Expected:** name is editable, type field is disabled/locked with an
   explanation.
4. **Municipality registry.** Sidebar → Municipalities. Confirm all 11
   real municipalities are listed (Compostela, Laak, Mabini, Maco,
   Maragusan, Mawab, Monkayo, Montevista, Nabunturan, New Bataan,
   Pantukan) plus the two "Sample Municipality —" rows from seed data.
   Edit Maco's nickname if it isn't already "Tigers" (confirm it
   saves), then add a brand-new test municipality ("UAT Test
   Municipality") and archive it again immediately.
   **Expected:** all 13 rows visible; edit and archive both succeed and
   show a success toast; the archived row still shows in the list with
   an "Archived" badge.
5. **Schools registry.** Sidebar → Schools. Add a school under "UAT Test
   Municipality" — wait, that's archived now; add it under Compostela
   instead ("UAT Test School," Secondary level). Confirm it appears in
   search when you search "UAT."
   **Expected:** the school is created and searchable immediately.
6. **Sports & Events catalog.** Sidebar → Sports, then Events. Confirm 14
   sports and the standard athletics track events are present. Add one
   new event under Athletics (any gender/age division, entry cap 2) and
   confirm it appears in the Events list.
   **Expected:** event created, shows correct sport/gender/division/cap.
7. **Audit log.** Sidebar → Audit log. Confirm the actions you just took
   in steps 3–6 (division update, school create, event create, etc.)
   appear, newest first, each with your account name and a timestamp.
   Search for "UAT" and confirm it filters to just your test records;
   filter by action type (e.g. "school.created") and confirm it narrows
   correctly.
   **Expected:** every step above has a matching audit row; search and
   filter both work.
8. **Confirm the account-creation limitation from the manual is real.**
   Sign out, go to `/register`, and self-register a brand-new account.
   Sign back in as Administrator and check the audit log — the
   registration itself won't appear there (self-registration isn't an
   audited action), but you should be able to confirm via
   `php artisan tinker` (`User::where('email', '…')->first()->role`)
   that the new account defaulted to `viewer`.
   **Expected:** matches `docs/manuals/admin-manual.md` §2 exactly — no
   in-app way to have created that account with a different role.
9. **Clean up.** Delete "UAT Test School" and the new Athletics event
   from steps 5–6 (or archive them if delete is refused because
   something now references them) so they don't linger in the UAT
   database. Restore "UAT Test Municipality" and delete it (it should
   still be empty).
   **Expected:** clean-up succeeds; nothing from this script is left
   behind except the audit trail of it happening.

## See also

`docs/manuals/admin-manual.md`, `docs/division.md`, `docs/registry.md`,
`docs/sports-catalog.md`, `docs/audit-trail.md`.
