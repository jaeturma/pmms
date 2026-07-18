# PMMS Laravel, Inertia, and React Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md) · [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md) · [domain-and-application-testing.md](domain-and-application-testing.md)

This document defines Laravel Feature-test, Inertia-specific, and React-component testing requirements. **No Pest test, React test file, or test-framework configuration is created here** — the repository currently has no frontend test framework installed (verified during this phase's inspection).

---

## 1. Laravel Feature Testing

Every HTTP-facing behavior requires Feature-level test coverage of:

| Target | What to Verify |
|---|---|
| HTTP requests | Correct routing, status codes, and response shape |
| Validation | Invalid input is rejected with the correct error structure |
| Authentication | Unauthenticated requests are correctly denied; authenticated requests resolve the correct User Account |
| Authorization | Per [../03-security/authorization-and-privileged-access-assurance.md](../03-security/authorization-and-privileged-access-assurance.md) — both positive and negative cases |
| Scope | A request scoped to Meet A cannot access Meet B's data |
| Assignment | An action requiring an active assignment is denied without one, even if the role is held |
| State transitions | An action is denied when the target resource is in an invalid state for it |
| Inertia responses | Correct component/props returned for a given request (Section 2) |
| API responses | Correct JSON contract for non-Inertia API endpoints |
| File uploads | Per [file-upload lifecycle testing, in api-contract-and-integration-testing.md](api-contract-and-integration-testing.md) |
| Downloads | Authorization re-checked at download time, not assumed from upload-time authorization |
| Exports | Per [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](../03-security/data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Queue dispatch | The correct job is dispatched to the correct queue, per [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) |
| Notifications | The correct notification is triggered for the correct recipient |
| Errors | Error responses are minimal and non-disclosing, per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) |
| Audit events | The correct audit event is produced for the action |

The existing starter-kit Feature tests (`tests/Feature/Auth/*`, `tests/Feature/Settings/*`) demonstrate the established pattern (Pest, `RefreshDatabase`, SQLite in-memory) that PMMS domain Feature tests extend — no new test framework is introduced for backend testing.

## 2. Inertia Testing

| Target | What to Verify |
|---|---|
| Page-component mapping | A given route renders the correct Inertia page component |
| Page props | Props contain exactly the data the page needs — no unnecessary sensitive hydration, restated from [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security) |
| Lazy props | Deferred/lazy props load correctly and only when requested |
| Partial reload behavior | Inertia's partial-reload mechanism returns only the requested prop subset |
| Validation errors | Server-side validation errors surface correctly in the page's error state |
| Authorization denial | A denied action correctly redirects/errors without leaking unauthorized data in the process |
| Sensitive-prop minimization | No Restricted/Highly Restricted field appears in a prop the current page doesn't need to display |
| Redirect behavior | Post-action redirects target the correct page with correct flash state |
| Flash messages | Success/error flash messages surface correctly after a redirect |
| Pagination | Paginated props carry correct metadata and page boundaries |
| Filters | Filter/search parameters correctly constrain the returned data set |
| Real-time reconciliation | A page that also receives Reverb-broadcast updates correctly reconciles broadcast data with its Inertia-provided state, never diverging into an inconsistent view |

## 3. React Testing

| Target | What to Verify |
|---|---|
| Design-system components | shadcn/ui-based components render and behave correctly across their documented states |
| Feature components | Domain-specific components (registration forms, eligibility review panels, etc.) behave correctly |
| Forms | Validation, submission, and error display behave correctly |
| Tables | Sorting, filtering, pagination render correctly |
| Charts | Data visualization components (where used) render correctly for representative data shapes |
| Brackets | Tournament bracket components correctly render tournament structure and progression |
| Scoreboards | Live scoreboard components correctly reflect current state and update on real-time events |
| Medal tally | Medal-tally display components correctly reflect certified, published data only |
| Athlete profile | Public/administrative athlete-profile views respect classification/masking rules (per [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Masking, Redaction, and De-Identification"](../03-security/data-sharing-export-and-public-disclosure-controls.md#masking-redaction-and-de-identification)) |
| QR screens | QR display/scan-confirmation screens render correctly |
| Loading states | Async operations show appropriate loading feedback |
| Error states | Failed operations show appropriate, non-disclosing error feedback |
| Empty states | Zero-result states render meaningfully, not as a blank/broken-looking screen |
| Permission-aware UI | UI correctly hides/disables actions the current user isn't authorized for — **as a usability convenience only**, never as the actual security boundary (restated absolutely from [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security)) |
| Accessibility | Per [accessibility-usability-and-compatibility-testing.md](accessibility-usability-and-compatibility-testing.md) |
| Responsive behavior | Components render correctly across supported viewport sizes |
| Dark and light themes | Components render correctly in both themes, consistent with the Tailwind 4 + shadcn/ui direction |

**Do not rely on snapshot testing alone** — a snapshot test confirms output hasn't changed, not that the output is correct; snapshot tests are a supplement to, never a substitute for, behavioral assertions about what a component actually does.

## 4. Frontend Test Framework (Not Yet Selected)

No React/TypeScript test framework (Vitest, Jest, Testing Library, Playwright, Cypress) is currently installed — confirmed absent from `package.json` during this phase's inspection. Selecting and configuring one is a Phase 0.8+ implementation decision, tracked in [quality-open-decisions.md](quality-open-decisions.md); this document defines what must be tested, not which tool tests it.

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably frontend test-framework selection (Vitest is the most natural fit given the Vite-based build already in place, but not committed here) and whether component/visual testing uses a tool like Storybook.
