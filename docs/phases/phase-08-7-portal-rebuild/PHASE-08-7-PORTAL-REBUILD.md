# Phase 08.7 – Public Portal Rebuild

## Goal
Completely replace the existing **public frontend**.

### Do NOT preserve
- Existing public layouts
- Existing public components
- Existing public cards
- Existing navigation
- Existing landing page

### Preserve
- Laravel backend
- Routes
- APIs
- Controllers
- Models
- Database
- Authentication
- Tournament logic
- Live scoring logic
- Admin theme

## New Frontend

Create a new application:

resources/js/apps/portal/

Use:
- React
- Tailwind CSS
- shadcn/ui

Arena is the UX inspiration only.

Create pages:
/
basketball
volleyball
athletics
boxing
baseball
softball
medal-tally
standings
results
schedules
venues
news
gallery
about

Each sport page is a lightweight mini portal.

Success:
The public portal should feel completely new while the admin panel remains unchanged.
