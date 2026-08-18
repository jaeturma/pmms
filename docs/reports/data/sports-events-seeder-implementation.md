# Sports Events seeder implementation

## Summary

- Reused: `sports`, `sport_categories`, `events`, `meet_sports`, and all existing schedule/result/registration relationships.
- New tables: none.
- Added columns: stable codes/slugs, classification, icon key, formats, team flags and ordering on sports; normalized metadata on categories; event metadata and medal flag on events; meet-specific status/description/venue notes/order on meet sports.
- Models: expanded safe fillable/casts and added `Meet::sports()`; existing relationships remain intact.
- Seed: 25 regular sports, four Paragames sports, canonical categories, default medal events, and optional DdOPAA Meet 2026 assignments.
- Media: icon keys are local lookup keys; no SVG or remote photo URL is stored. Existing nullable upload relationship remains the photo mechanism.
- Personnel, venues, and schedules are not seeded into canonical sport records.

## Commands

Take and record a production backup first, then run:

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\SportsSeeder
php artisan db:seed --class=Database\\Seeders\\SportCategoriesSeeder
php artisan db:seed --class=Database\\Seeders\\SportEventsSeeder
php artisan db:seed --class=Database\\Seeders\\DdOPAA2026SportsSeeder
```

The meet-specific seeder safely skips assignment if `DdOPAA Meet 2026` does not exist. All seeders use stable codes/slugs with `updateOrCreate` and are safe to rerun.

## Manual confirmation

Obtain the actual workbook and approved technical rules to resolve every entry marked in the companion review report, especially sex categories, exact events, weight classes, team limits, Paragames classifications, and participation phrases such as “12 CORE GROUP + 3 HYBRID.”

## Verification result (2026-08-13)

- Full automated suite: 1,415 tests passed with 7,108 assertions.
- Focused catalog/domain suite: 139 tests passed with 937 assertions.
- TypeScript: `npm run types:check` passed.
- Formatting: Pint passed.
- Routes: both `/sports-directory` (public) and `/sports` (authenticated administration) are registered; canonical cards expose classification, icon key, description, category count, and direct portal slug.
- Development migration/seeding: applied successfully without a wipe on 2026-08-13. The targeted seeder completed twice; the database contains 29 sports (25 regular, four Paragames), 47 categories, 84 events, and 29 DdOPAA Meet 2026 sport assignments.
