# Sports Events seed review

## Source and checkpoint

The implementation used the explicit data transcribed in the attached request. The referenced `Sports Events.xlsx` workbook was not present in the supplied attachments, so wording not reproduced in the request was not inferred. Before production deployment, take a normal database backup and record its location and timestamp. No destructive database command is part of this change.

## Existing structure reused

PMMS already had `sports`, `sport_categories`, `events`, and `meet_sports`. The working `events` table is retained as the competition/medal-event level because registrations, schedules, matches, results, and medals already depend on it. Creating a parallel `sport_events` table would split those relationships. Existing category and meet pivots are also retained.

## Reviewed catalog

| Sport | Classification | Elementary | Secondary | Participation | Generated category | Ambiguity / confirmation |
|---|---|---:|---:|---|---|---|
| Archery | regular | No | Yes | Individual | Secondary Open | Confirm sex categories and event list |
| Basketball | regular | Yes | Yes | Team | Elementary/Secondary Open | Confirm boys/girls and roster limits |
| Basketball 3x3 | regular | No | Yes | Team | Secondary Open | Confirm roster limits and sex categories |
| Gymnastics | regular | Existing PMMS says yes | Existing PMMS says yes | Individual/judged | Level Open plus WAG, MAG, RG disciplines | Confirm apparatus, sex eligibility, and medal events |
| Dancesports | regular | Existing PMMS says yes | Existing PMMS says yes | Pair Latin / Pair Standard appears in source notes | Level Open | Exact styles, pair composition, and event list needed |
| Futsal | regular | Existing PMMS says yes | Existing PMMS says yes | Team | Elementary/Secondary Open | Confirm sex categories and roster limits |
| Sepak Takraw | regular | Unconfirmed | Existing PMMS says yes | Team; source wording not supplied | Secondary Open | Confirm event/team composition |
| Bocce | paragames | Existing PMMS says yes | Existing PMMS says yes | Source wording not supplied | Elementary/Secondary Open | Confirm classifications, sex, and team format |
| Goalball | paragames | Unconfirmed | Unconfirmed | Team | Open / Configuration Pending | Confirm level, sex, and roster limits |
| Para Athletics | paragames | Unconfirmed | Unconfirmed | Individual/relay | Open / Configuration Pending | Confirm athlete classifications and event program |
| Para Swimming | paragames | Unconfirmed | Unconfirmed | Individual/relay | Open / Configuration Pending | Confirm athlete classifications and event program |
| Other 18 catalog sports | regular | Based only on existing PMMS descriptions where available | Based only on existing PMMS descriptions where available | Preserved as team/individual at sport level | Neutral Open categories | Validate against the missing workbook and approved technical rules |

The catalog contains 25 regular sports and four Paragames sports (29 total). This conflicts with the earlier approximate count of 28 and requires confirmation; the explicit spreadsheet-derived list of 29 controls this seed.

Unknown sex definitions are stored as the project's neutral `mixed` value with a `participation_notes` warning. They were not expanded blindly into boys and girls. Unknown levels use an “Open / Configuration Pending” category.

## Canonicalization

Legacy names are normalized: `Billiard` to `Billiards`, `Paragames - Boccee` to `Bocce`, `Paragames - Goal Ball` to `Goalball`, and the two prefixed para records to `Para Athletics` and `Para Swimming`. Gymnastics remains one sport with WAG, MAG, and RG disciplines.
