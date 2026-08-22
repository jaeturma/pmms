# DdOPAA 2026 School Master List

Source: `PMMS_DdOPAA_2026_Schools_Migration.sql`  
Records: 461 approved production schools

## Import rules

- Stable identifier: official School ID (`schools.school_id_code`).
- Initial municipality: unassigned (`district_id = NULL`).
- Initial school district: unassigned (`school_district_id = NULL`).
- Municipality and school district are assigned later by an authorized PMMS administrator.
- The seeder updates only the approved name and Public/Private classification of an existing School ID. It preserves municipality, school district, level, address, active state, and other user-maintained data.
- School names are not used as identity. The source contains 14 repeated exact names attached to different official School IDs; all are preserved.

## Source verification

| Check | Result |
|---|---:|
| Total schools | 461 |
| Public | 420 |
| Private | 41 |
| Duplicate School IDs | 0 |
| Repeated exact names with different IDs | 14 name groups |
| Invalid School IDs | 0 |

Run the isolated seeder safely on an existing database with:

```bash
php artisan db:seed --class=DdOPAA2026SchoolSeeder
```

The normal `DatabaseSeeder` runs it after geographic reference data and before meet/sport/personnel imports.

## Assignment workflow

The Schools registry identifies unassigned records and permits an authorized Super Administrator, approved Central ICT member, or authorized Meet Manager to select a municipality and one of that municipality's school districts. Server-side validation rejects a school district belonging to another municipality. Coaches and monitoring roles may use or view authorized school records but cannot change the master list.
