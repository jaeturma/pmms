# DdOPAA Provincial Meet 2026 — Source Data Review

## Source workbook

`TWG TM and TO.xlsx`

Sheets reviewed:

- TWG
- TM and TO
- DSC
- Players

## Extracted records

- TWG membership rows: **144**
- DSC assignments: **18**
- Sport/event headers in final TM/TO list: **28**
- Sport personnel assignment rows: **623**
- Unique people across TWG/DSC/TM-TO: **780**
- Unique sport personnel queued for user provisioning: **622**

## Normalized sport-personnel roles

- ASSISTANT_TM_TRACK: 1
- ASSISTANT_TOURNAMENT_MANAGER: 3
- TECHNICAL_OFFICIAL: 525
- TOURNAMENT_ICT_TECHNICAL_OFFICIAL: 19
- TOURNAMENT_MANAGER: 54
- TOURNAMENT_MANAGER_FIELD: 1
- TOURNAMENT_MANAGER_TRACK: 1
- TOURNAMENT_SECRETARY: 12
- TOURNAMENT_SECRETARY_ICT: 7

## Important source-data issues preserved for confirmation

1. **Final TM/TO sheet contains 28 event headers.**
2. The Players sheet includes **Basketball 3x3**, but the final TM/TO assignment sheet does not have a Basketball 3x3 event header.
3. The final TM/TO sheet contains **“WEIGHTLIFTING / KICKBOXING” as one combined assignment header**, while the Players sheet lists Weightlifting separately and does not show Kickboxing as a separate player-rule row.
4. Some source District values are municipality-only (for example `NABUNTURAN`, `MARAGUSAN`, `PANTUKAN`) instead of one of the 18 explicit School Districts. The migration preserves the original text and maps the Municipality, but leaves `school_district_id` null when the exact School District cannot be resolved safely.
5. A source typo `NABUNRUEAN WEST` is normalized to `NABUNTURAN WEST`.
6. Designations such as `TOURNAMENT SECRETARY/ICT`, `ICT-TECHNICAL OFFICIAL`, and `TECHNICAL OFFICIAL / ICT` are preserved in `role_label` and normalized to reusable role codes.
7. Congressional District assignments are not present in this workbook. The migration intentionally leaves `congressional_district_code` for Codex to map from the existing official geographic master data rather than inventing values.

## Migration safety

The SQL is additive and idempotent where practical. Before applying to the existing Laravel database, Codex must map these target structures to existing models/tables and avoid duplicate domain tables.

Do not run `migrate:fresh`, `db:wipe`, or destructive replacements on an existing PMMS database.
