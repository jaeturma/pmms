# Announcements

WP-04-05 — the only new entity of Phase 4. Public advisories for the portal:
plain text, general or tied to one meet, visible publicly only while published.

## Data model

`announcements` — optional `meet_id` (FK, cascade — a meet's advisories go with
it), `title` (≤160), `body` (text, ≤2000 via validation, plain text only — no
rich text or attachments), `is_published` + `published_at` (cleared on
unpublish, fresh timestamp on republish), `created_by` (users FK, null on
delete). `Announcement::published()` is the public-visibility scope.

## Authorization & audit

The whole internal module (list included) sits in the `role:admin,organizer`
route group — swept by `AuthorizationMatrixTest`. Every mutation is audited:
`announcement.created|updated|published|unpublished|deleted`, with title and
meet in context.

## Internal UI

`resources/js/pages/announcements/index.tsx` (sidebar entry for managers) —
shared registry pattern: search by title, pagination, create/edit dialog (meet
select with "General", title, plain-text body via the new `ui/textarea`
primitive), Publish/Unpublish and Delete confirmations, Draft/Published badges.

## Public display

Published announcements render through the shared `PublicAnnouncements`
component (nothing renders when there are none): the portal home shows the
latest five across meets (meet name labeled), a public meet page shows that
meet's own published announcements only. Drafts are invisible everywhere
public — enforced by the `published()` scope in `PortalController`.
