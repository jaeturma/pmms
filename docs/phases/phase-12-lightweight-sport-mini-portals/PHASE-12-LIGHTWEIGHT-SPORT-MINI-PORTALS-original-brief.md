# Phase 08.6 — Lightweight Sport Mini Portals

## Project

**Provincial Meet Management System (PMMS)**  
Public event domain: **https://www.ddopaa.live**

## Phase Objective

Create lightweight, responsive sport mini portals for the public-facing DdOPAA website.

Each sport page must present the most important live and tournament information without becoming a content-heavy sports website.

Example routes:

- `/basketball`
- `/volleyball`
- `/baseball`
- `/softball`
- `/football`
- `/sepak-takraw`
- `/badminton`
- `/table-tennis`
- `/chess`
- `/boxing`
- `/athletics`
- `/swimming`

Example public URL:

```text
https://www.ddopaa.live/basketball
```

Do not add a `/live` route prefix. The `.live` domain already communicates that this is the official live event platform.

---

# 1. Scope Boundary

This is a **frontend-focused phase**.

## Preserve the existing backend

Do not modify unless a minor compatibility fix is absolutely necessary:

- Laravel models
- Controllers
- Service classes
- Database migrations
- Tournament rules
- Scoring logic
- Medal computation
- Athlete eligibility logic
- Authentication
- Authorization
- Policies
- Middleware
- Admin routes
- Admin theme
- Existing permissions
- Existing API contracts
- Existing Inertia props

The current administration interface must retain its existing theme and workflow.

## Allowed frontend changes

Claude may redesign or replace public-facing:

- React pages
- Public layouts
- Public components
- Tailwind classes
- Cards
- Navigation structures
- Section layouts
- Responsive behavior
- Loading states
- Empty states
- Public sport-page composition

Do not change route names, API response contracts, or business logic without documenting and justifying the change.

---

# 2. Required Mini Portal Sections

Every sport mini portal must contain only these core sections:

1. **Live Now**
2. **Today’s Games**
3. **Completed Games**
4. **Upcoming Games**
5. **Standings**
6. **Leading Scorers**
7. **Tournament Bracket**
8. **Venue Information**

Do not add news feeds, galleries, long-form articles, videos, social walls, athlete profiles, sponsor carousels, or decorative content in this phase.

The page must remain fast, focused, and practical.

---

# 3. Shared Page Structure

Use one reusable sport mini-portal system instead of creating separate duplicated implementations for every sport.

Recommended structure:

```text
resources/js/
├── Pages/Public/Sports/
│   ├── SportPortal.tsx
│   └── SportPortalFallback.tsx
├── Components/Public/Sports/
│   ├── SportPortalHeader.tsx
│   ├── LiveNowCard.tsx
│   ├── TodayGamesList.tsx
│   ├── CompletedGamesList.tsx
│   ├── UpcomingGamesList.tsx
│   ├── StandingsTable.tsx
│   ├── LeadingScorersTable.tsx
│   ├── TournamentBracketPreview.tsx
│   ├── VenueInformationCard.tsx
│   ├── SportSection.tsx
│   ├── SportStatusBadge.tsx
│   ├── SportPortalSkeleton.tsx
│   └── EmptySportState.tsx
├── Config/
│   └── publicSports.ts
└── Types/
    └── publicSports.ts
```

Adapt paths to the existing project structure. Do not create a competing architecture if equivalent reusable components already exist.

---

# 4. Sport Configuration

Create a single configuration source for supported public sports.

Example:

```ts
export type SportPortalConfig = {
    slug: string;
    name: string;
    shortName?: string;
    icon?: string;
    scoringType:
        | 'team-score'
        | 'sets'
        | 'combat'
        | 'race'
        | 'time-distance'
        | 'rank-only';
    supportsStandings: boolean;
    supportsLeadingScorers: boolean;
    supportsBracket: boolean;
    terminology?: {
        game?: string;
        period?: string;
        points?: string;
    };
};
```

Use the configuration to adapt labels and layouts without duplicating complete pages.

Examples:

- Basketball: games, quarter, points
- Volleyball: matches, set, points
- Boxing: bouts, round, decision
- Athletics: events, heat/final, time/distance
- Chess: matches, round, points

If a section is not applicable to a sport, render a sport-appropriate compact alternative or a clear “Not applicable for this sport” state. Do not fabricate data.

---

# 5. Design Direction

## Public visual language

Use the cloned **Arena-inspired public frontend** for:

- Typography hierarchy
- Header composition
- Section spacing
- Container widths
- Cards
- Buttons
- Navigation patterns
- Responsive layout
- Overall sports-event atmosphere

Use the existing PMMS/DdOPAA branding and colors.

Do not import Bootstrap or copy Arena source HTML/CSS.

## Specialized sports presentation

Use a cleaner sports-broadcast treatment for:

- Live score cards
- Standings tables
- Leading scorer tables
- Bracket previews
- Status badges

The page should feel modern and professional but remain lightweight.

## Existing admin interface

Do not apply the Arena design to the backend/admin interface.

---

# 6. Desktop Layout

Recommended desktop order:

```text
Sport Portal Header

Live Now — full width

Today’s Games — full width

Completed Games | Upcoming Games

Standings | Leading Scorers

Tournament Bracket — full width

Venue Information — full width or compact two-column layout
```

Use a centered responsive container. Avoid excessive whitespace and oversized decorative sections.

---

# 7. Mobile Layout

On mobile:

- Use a single-column layout.
- Prioritize score, time, teams, venue, and status.
- Avoid wide tables when cards are more readable.
- Make standings horizontally scrollable only when unavoidable.
- Use compact table headers.
- Allow the bracket preview to open in a dedicated full-screen or modal view.
- Keep tap targets accessible.
- Do not use hover-only interaction.
- Avoid fixed-height sections that can clip content.

Target low-end Android phones and mobile-data users.

---

# 8. Section Requirements

## 8.1 Live Now

Show only one featured active game or event by default.

Display:

- Sport name
- Tournament stage or category
- Competitors or teams
- Current score or result state
- Quarter, set, round, heat, or event phase
- Game clock when applicable
- Venue
- Live status
- Link to the full scoreboard or event detail

Do not display advanced statistics on the mini portal.

When multiple events are live, show the most relevant featured event and a compact “Other live events” count or selector only if the existing backend supports it efficiently.

When no event is live, show a compact empty state and the next scheduled event.

## 8.2 Today’s Games

Show a maximum of 10 items.

Display:

- Scheduled time
- Competitors or teams
- Venue
- Tournament stage/category
- Status
- Score when available

Order by scheduled time.

## 8.3 Completed Games

Show a maximum of 10 recent completed games or events.

Display:

- Final score or final result
- Winner
- Completion status
- Tournament stage/category
- Link to result details

Do not load the full historical season or meet record on initial render.

## 8.4 Upcoming Games

Show a maximum of 10 upcoming games or events.

Display:

- Date and time
- Competitors or teams
- Venue
- Tournament stage/category

Prefer the next chronological events.

## 8.5 Standings

Show only essential columns appropriate to the sport.

For common team sports:

- Rank
- Team or municipality
- Played
- Wins
- Losses
- Draws when applicable
- Standing points

Do not include advanced statistics unless already required by existing tournament rules.

## 8.6 Leading Scorers

Show only the top 5.

For basketball-like sports, display:

- Rank
- Athlete
- Team or municipality
- Games played
- Total points
- Average points

For sports where “scorers” are not applicable, adapt the label and metric:

- Volleyball: top point scorers if available
- Football: goals
- Baseball/Softball: runs or another existing official statistic
- Boxing: not applicable; show top-performing delegation only if already supported
- Athletics/Swimming: top event performers or record leaders only if existing data supports it
- Chess: top individual points

Do not create unsupported statistics.

## 8.7 Tournament Bracket

Show a simplified preview first.

Requirements:

- Lazy-load when the section approaches the viewport.
- Display only the relevant current tournament bracket.
- Provide a “View Full Bracket” action.
- On mobile, use a dedicated full-screen view, modal, or horizontal scroller.
- Do not use a heavy diagramming library unless it is already installed and necessary.

If the sport uses round-robin only, display a compact tournament-stage summary instead of an artificial knockout bracket.

## 8.8 Venue Information

Display:

- Venue name
- Municipality
- Address or location
- Current event when applicable
- Next scheduled game or event
- Directions link

Do not embed a heavy interactive map on initial load.

Use a normal external map link or a lightweight static location treatment.

---

# 9. Performance Requirements

The primary goal is fast loading and low bandwidth use.

## Initial load

- Fetch data only for the currently opened sport.
- Do not preload all sports.
- Do not preload full brackets.
- Do not preload large historical datasets.
- Avoid large hero videos.
- Avoid autoplay media.
- Avoid full-resolution decorative background images.
- Use optimized WebP or AVIF assets when images are needed.
- Use SVG icons where appropriate.
- Keep the JavaScript bundle small.
- Avoid adding heavy charting libraries.
- Avoid unnecessary animation libraries.

## Data limits

- Live Now: 1 featured item
- Today’s Games: maximum 10
- Completed Games: maximum 10
- Upcoming Games: maximum 10
- Leading Scorers: top 5
- Standings: current competition only
- Bracket: preview first; full data on demand

## Refresh behavior

Recommended fallback polling intervals:

| Data | Refresh interval |
|---|---:|
| Featured live score | 5–10 seconds |
| Today’s games | 30–60 seconds |
| Completed games | 60 seconds |
| Upcoming games | 5 minutes |
| Standings | 2–5 minutes |
| Leading scorers | 2–5 minutes |
| Tournament bracket | 5 minutes or on demand |
| Venue information | Cached |

Use Laravel Reverb or existing real-time infrastructure if already available and stable.

Do not introduce a new real-time stack solely for this phase.

## Visibility-aware polling

- Stop or pause polling when the browser tab is hidden.
- Resume when the tab becomes visible.
- Poll live data only when an active event exists.
- Do not poll static venue data repeatedly.
- Prevent duplicate timers and memory leaks.

## Caching

Use existing caching infrastructure when available.

Good cache candidates:

- Standings
- Completed games
- Upcoming games
- Leading scorers
- Bracket preview
- Venue details

Do not cache actively changing game clocks in a way that creates stale live displays.

---

# 10. Loading and Error States

Every section must have:

- Skeleton loading state
- Empty state
- Error state
- Retry action when appropriate

Do not block the full page because one section failed.

Render independent sections progressively.

Examples:

- Live score unavailable → show the next scheduled game.
- Standings unavailable → show a compact retry state.
- Bracket unavailable → hide the preview and show a text link when possible.
- Venue missing → show venue name only if that is the only confirmed field.

---

# 11. Accessibility

- Use semantic headings.
- Ensure score updates have appropriate accessible announcements without excessive screen-reader noise.
- Maintain sufficient contrast.
- Do not communicate live/final status by color alone.
- Provide visible focus states.
- Use accessible table markup.
- Label icons and controls.
- Respect reduced-motion preferences.

---

# 12. SEO and Public Metadata

Each sport route should have lightweight metadata:

```text
Basketball | DdOPAA Live
Volleyball | DdOPAA Live
Athletics | DdOPAA Live
```

Include:

- Page title
- Meta description
- Canonical URL
- Social preview metadata if already supported

Do not add large page-specific social images in this phase unless an optimized shared image already exists.

---

# 13. Routing Requirements

Required pattern:

```text
/{sportSlug}
```

Examples:

```text
/basketball
/volleyball
/athletics
/boxing
```

Do not create:

```text
/live/basketball
/sports/basketball/live
```

The public domain itself is the live event identity:

```text
https://www.ddopaa.live/basketball
```

Preserve existing routes if they already match this structure. Add route aliases only when necessary and without breaking existing links.

---

# 14. Implementation Workflow

Claude must follow this order.

## Step 1 — Inspect

Inspect the existing:

- Public routes
- Sport pages
- Inertia props
- APIs
- Scoreboard components
- Standings components
- Tournament bracket components
- Existing Arena-cloned components
- Design tokens
- Admin/public layout boundaries

Document findings before changing code.

## Step 2 — Map data

Create a mapping between available backend data and each required section.

Do not assume fields exist.

Identify:

- Existing fields
- Missing fields
- Fields requiring formatting only
- Fields that are unavailable and must show an empty state

## Step 3 — Build shared components

Implement the reusable sport portal shell and shared sections.

Do not start by duplicating separate sport pages.

## Step 4 — Implement basketball first

Use `/basketball` as the reference implementation.

Complete and validate:

- Desktop
- Tablet
- Mobile
- Loading states
- Empty states
- Live refresh behavior
- Performance

## Step 5 — Generalize

After basketball is stable, adapt the configuration and components for other sports.

## Step 6 — Handle sport-specific exceptions

Create small adapters for:

- Athletics
- Swimming
- Boxing
- Chess

Do not fork the complete portal page unless the data model makes reuse impossible.

## Step 7 — Test

Complete automated and manual testing.

## Step 8 — Document

Update project documentation and implementation notes.

---

# 15. Testing Requirements

## Backend contract tests

Confirm that public endpoints and Inertia responses still satisfy existing contracts.

Do not rewrite backend tests unnecessarily.

## Frontend tests

Test:

- Correct section rendering
- Live vs no-live states
- Maximum item limits
- Top-five scorer limit
- Visibility-aware polling
- Responsive layout behavior
- Empty and error states
- Sport-specific terminology
- Lazy-loaded bracket

## Manual test matrix

Test at minimum:

- Desktop Chrome
- Desktop Edge
- Android mobile viewport
- Slow 4G simulation
- Reduced-motion mode
- Hidden tab and resumed tab
- No live event
- One live event
- Multiple live events
- Missing standings
- Missing bracket
- Missing venue data

## Performance target

Do not degrade the current public portal performance.

Aim for:

- Minimal requests on initial load
- No requests for unrelated sports
- No duplicate polling
- No major layout shift
- No oversized images
- No heavy unused JavaScript libraries

---

# 16. Required Documentation

Create or update:

```text
docs/public-sport-portals/
├── architecture.md
├── route-map.md
├── data-contract-map.md
├── sport-configuration.md
├── performance-strategy.md
├── testing-checklist.md
└── implementation-summary.md
```

The implementation summary must include:

- Files created
- Files modified
- Components reused
- Backend contracts preserved
- Known limitations
- Sport-specific exceptions
- Performance decisions
- Test results

---

# 17. Acceptance Criteria

This phase is accepted only when:

1. `/basketball` works as a complete lightweight mini portal.
2. The page contains exactly the eight required section types.
3. Only one featured live event is shown initially.
4. Game lists are limited to 10 items each.
5. Leading scorers are limited to the top 5.
6. The bracket is lazy-loaded or loaded on demand.
7. Venue information does not load a heavy embedded map.
8. Data for other sports is not fetched on the basketball page.
9. Polling pauses when the page is hidden.
10. Polling occurs only when live data requires it.
11. The design uses the existing Arena-inspired public frontend language.
12. Existing PMMS/DdOPAA branding remains intact.
13. The admin theme is unchanged.
14. Backend routes, logic, permissions, and contracts remain intact.
15. The page is usable on low-end mobile devices.
16. Loading, empty, and error states exist for every section.
17. The implementation is reusable across supported sports.
18. Athletics, boxing, swimming, and chess receive appropriate adaptations.
19. Automated tests pass.
20. Documentation is complete.

---

# 18. Definition of Done

The work is done when:

- Code is implemented and formatted.
- Existing linting and type checks pass.
- Existing backend tests pass.
- New relevant frontend tests pass.
- No unrelated backend changes were introduced.
- No admin-theme changes were introduced.
- Public routes work without `/live`.
- Mobile performance has been manually verified.
- Polling behavior has been verified.
- Documentation has been updated.
- A concise implementation summary is provided.

---

# 19. Claude Execution Instruction

Use this exact execution behavior:

```text
Implement Phase 08.6 — Lightweight Sport Mini Portals.

Read the entire phase instruction before changing code.

First inspect the existing application, especially:
- public routes
- sport pages
- Arena-cloned public components
- Inertia props and APIs
- scoreboards
- standings
- brackets
- venue data
- public/admin layout boundaries

Do not modify backend business logic, database structure, permissions, authentication, scoring logic, tournament logic, or the existing admin theme.

Implement /basketball first as the reference mini portal with only:
1. Live Now
2. Today’s Games
3. Completed Games
4. Upcoming Games
5. Standings
6. Leading Scorers
7. Tournament Bracket
8. Venue Information

Use reusable React, TypeScript, Tailwind CSS 4, and existing shadcn/ui components.

Use the Arena-inspired public visual language already being cloned, but preserve PMMS and DdOPAA branding and colors.

Prioritize low bandwidth and low server usage:
- fetch only the current sport
- one featured live event
- maximum 10 items per game section
- top 5 scorers
- lazy-load bracket
- no autoplay media
- no heavy maps
- no large decorative backgrounds
- pause polling when hidden
- poll only while an event is live
- cache non-live sections using existing infrastructure

Before implementation, produce a concise inspection report and data-contract map.

After implementation, run applicable tests and provide:
- summary of changes
- files modified
- test results
- known limitations
- confirmation that backend and admin theme were preserved
```

---

# 20. Explicit Non-Goals

Do not implement in this phase:

- Full athlete profiles
- Full player statistics dashboards
- News and articles per sport
- Sport galleries
- Video streaming
- Sponsor modules
- Social media feeds
- Fantasy features
- Predictions
- AI commentary
- Complex analytics
- Heavy charts
- New mobile app
- Separate public backend
- New real-time infrastructure
- Admin redesign
- Backend refactoring unrelated to compatibility

Keep this phase minimal, reusable, performant, and production-ready.
