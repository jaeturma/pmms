# DdOPAA 2026 final import report

## Verified source and isolated import

| Measure | Count |
|---|---:|
| Final TM/TO event headers | 28 |
| Source sport personnel rows | 623 |
| Safely mapped source personnel rows | 615 |
| Atomic PMMS MeetSport assignments | 641 |
| Unique people across TWG/DSC/TM-TO | 780 |
| Pending account provisions | 614 |
| TWG memberships | 144 |
| DSC assignments | 18 |
| Users linked/provisioned | 0 (activation intentionally not automated) |
| Duplicate canonical people created on second run | 0 |

The automated test runs the complete seeder twice and verifies unchanged counts. Atomic assignment count is higher than mapped source rows because 7 Secretary/ICT and 19 ICT/Technical Official rows are split into two scoped roles without duplicating people.

## Resolution queue

- Eight personnel rows under `WEIGHTLIFTING_KICKBOXING` are preserved in the source fixture but not imported until the intended separate sport scope is confirmed.
- Basketball 3x3 is present outside the final TM/TO headers; no personnel were inferred.
- Municipality-only source District labels retain their text and use a null School District when no exact canonical alias exists.
- The known `NABUNRUEAN WEST` typo resolves to Nabunturan West through the reviewed source normalization.
- Congressional District values were not present and were not invented.

## Deployment checkpoint

Before production deployment, create a database backup, record `php artisan migrate:status`, run `php artisan migrate --pretend`, then run `php artisan migrate` and `php artisan db:seed --class=Database\\Seeders\\DdOPAA2026FinalSeeder`. Do not use `migrate:fresh` or `db:wipe`. Review this resolution queue before activating any account provision.

