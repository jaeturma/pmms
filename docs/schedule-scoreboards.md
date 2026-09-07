# Schedule scoreboards

In Schedule of Events, choose a Basketball, Baseball, or Boxing event and enable **Live Scoreboard**. Choose the initial viewer label: **Test**, **Finals Game**, or **Championship Game**. Overlapping schedule slots are allowed; normal event, venue, area, and time validation still applies.

Administrators and actively assigned Tournament ICT operators can open the **Scoreboard** menu. Each schedule board has an operator console and a viewer link. Public viewer links require a published meet.

Start a run with the two side labels. The existing sport controls operate scores, clocks, periods, and other sport details. **Reset / new run** starts a fresh session from zero using the selected game label, retains prior sessions and their scores, and records an audit entry. It also works after a run ends. These schedule boards do not create or change submitted results or medal awards; submit results through Results.

On the **public** board the game label becomes a broadcast-style title: `Championship Game (TEAM "Level-up Warriors" VS TEAM "Power Voltz")`. Each side's team name is its `District` nickname (`ScoringSession::districtNicknameFor()` resolves the side label — the whole label first, then the part before an en/em dash, so `"Compostela – J. Cruz"` still matches the Compostela district); a side that matches no district, or a district with no nickname set, shows its raw side label instead.

Coach links remain editable through Registration after a result is accepted. The Results rank table reads current athlete coach links, and reassignment records an audit entry. Results can be filtered by sport and the four display statuses. Trail, Issues, and Result Evidence open modals; Issues appears only when a concern is present.

Apply the schema change before using schedule boards:

```shell
php artisan migrate --path=database/migrations/2026_09_06_000000_add_schedule_scoreboard_mode.php --force
```

The migration adds the nullable `matches.scoreboard_mode` field. Existing match scoring keeps its existing result workflow.
