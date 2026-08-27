# Content Management alignment

## 1. Existing News implementation

There is no `News` model or news administration module. The public portal's `portal/news` page currently presents published `Announcement` rows for one meet. This conflates editorial stories with short operational advisories and does not support slugs, article bodies, featured images, scheduling, or archives.

## 2. Existing Announcements

`Announcement`, its migration, controller, authenticated CRUD routes, React index, audit events, factories, and feature tests already exist. Publication is a boolean plus `published_at`; content may be global or meet-specific. Access is granted through `User::canManageAnnouncements()`, including active members of the management team whose `source_code` is `INFORMATION`. The implementation lacks scheduling, audience/priority fields, archives, role/sport/delegation targeting, and expiry-aware public filtering.

## 3. Existing Gallery

There is no gallery/media domain model. The public gallery is a grid of contested-sport identity tiles, not uploaded photographs. PMMS does have a private `FileUpload` model, `FileUploadService`, local/private storage, authorization, auditing, and image validation patterns. Spatie Media Library is not installed. Gallery should reuse `FileUpload` records and private storage, adding controlled thumbnail/display endpoints rather than exposing storage paths.

## 4. Existing FAQ

There is no FAQ model or administration module. The public FAQ page contains four hard-coded questions derived from meet data. It has no publication, ordering, category, archive, search, or editorial workflow.

## 5. Current permissions

PMMS uses role helpers, management-team membership, meet-sport assignments, policies/gates, and a small `Permission` enum. Information ownership already has a reliable production mapping: an active `ManagementTeamMember` attached to a team with `source_code = INFORMATION`. Tournament ICT and Tournament Secretary scope is represented by active `MeetSportAssignment` rows. `canManageAnnouncements()` already recognizes Information Team; Content Management should build granular capability methods on these existing identities and enforce them on every controller action.

## 6. Missing functions

- Separate News and FAQ records and workflows.
- Moderated gallery candidates, review states, contributor scope, private images, duplicate hashes, daily counts and publish limits.
- Content dashboard and permission-aware menu grouping.
- Announcement priority, audience, schedule, expiry and archive state.
- Public routes that return published, currently-visible content only.
- Audit coverage for editorial state transitions.

## 7. Proposed menu structure

`Content Management` groups News, Gallery, Announcements and FAQ. Information Team and administrators receive all four links. Tournament ICT/Secretary with active assignments receive Gallery only. Visibility is backed by shared capability flags; it is not the authorization boundary.

## 8. Gallery moderation flow

Contributors upload multiple images against an assigned `MeetSport` as draft candidates and submit them for review. Information Team can update final copy, approve/reject, feature, order, publish/unpublish and archive. Contributor edits are limited to their own draft/rejected items. Public delivery accepts published records only. Candidate guidance is 5–10 per sport/event/day; publication enforcement defaults to five per sport/day and is configurable.

## 9. Public portal integration

The existing portal shell remains. Gallery changes from sport tiles to published photo cards. News reads separate published News records. FAQ reads published FAQ records and retains lightweight search/category filtering. Announcements remain advisories and apply publication window/audience rules. Public props omit uploader identity, review notes, rejection reasons, hashes and private paths.

## 10. Migrations required

Non-destructive migrations are required for `news_items`, `faq_items`, and `gallery_items`, plus additive announcement workflow/targeting columns. Existing tables are not rebuilt and existing announcements remain valid through defaults.

## 11. Tests

Focused feature tests cover Information Team management, ICT/Secretary assigned-sport candidate uploads, unrelated-sport and publication denial, state visibility, configurable daily publication limits, private image delivery, menu capability props, News/FAQ publication filtering, announcement windows/audience, and audit events.
