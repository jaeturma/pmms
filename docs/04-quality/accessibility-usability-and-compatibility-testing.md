# PMMS Accessibility, Usability, and Compatibility Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../00-product/phase-0.1-product-foundation.md, Section 8](../00-product/phase-0.1-product-foundation.md#8-product-principles) · [laravel-inertia-react-testing.md](laravel-inertia-react-testing.md) · [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)

This document defines accessibility, usability-validation, and compatibility-testing requirements. **No compatibility matrix is finalized without evidence**, per the phase's working instructions.

---

## 1. Accessibility Testing

| Area | What to Verify |
|---|---|
| Keyboard navigation | Every interactive element is reachable and operable via keyboard alone |
| Focus order | Tab order follows a logical, predictable sequence |
| Focus visibility | The currently-focused element is always visibly indicated |
| Screen-reader support | Content and controls are correctly announced by common screen readers |
| Labels | Every form control has an associated, meaningful label |
| Error associations | Validation errors are programmatically associated with their field |
| Contrast | Text/background contrast meets accessibility guidance |
| Text scaling | The UI remains usable when text is scaled up |
| Responsive reflow | Content reflows usably at various zoom/viewport combinations |
| Touch target size | Interactive elements are large enough for reliable touch interaction |
| Motion reduction | Users who prefer reduced motion are respected where animation is used |
| Table navigation | Data tables (schedules, results, standings) are navigable and comprehensible via assistive technology |
| Chart alternatives | Any chart/visualization has an accessible data-table or text alternative |
| Scoreboard readability | Live-scoreboard displays remain readable at a distance and under varied lighting (a physical-venue-display concern, not purely a software one) |
| Dark and light themes | Both themes meet the same accessibility bar |
| Public kiosk accessibility | Public-facing kiosk displays are usable by the general public, including users with disabilities |

**WCAG is used as a candidate reference requiring final validation** — restated per the phase's working instructions; PMMS's accessibility testing targets WCAG guidance as a strong baseline without asserting formal conformance-level certification until a dedicated accessibility review confirms it.

## 2. Usability Validation

| Task Area | What to Validate |
|---|---|
| Registration tasks | A delegation representative can complete athlete registration without confusion |
| Eligibility review | A reviewer can efficiently process a queue of eligibility cases |
| Score entry | A scorer can enter scores quickly and accurately under real competition time pressure |
| Tournament management | A Tournament Manager can set up and adjust brackets/schedules without excessive friction |
| Committee dashboards | Each committee's dashboard surfaces the information that committee actually needs, not a generic catch-all view |
| QR scanning | A gate operator can scan and validate credentials quickly, including under poor lighting/connectivity |
| Mobile offline use | Field staff can use the mobile app effectively while disconnected |
| Public results | A member of the public can find schedule/result information without assistance |
| Medal tally | Medal-tally information is clear and unambiguous to a public viewer |
| Error recovery | A user who makes a mistake can understand and correct it without needing external support |
| Low digital literacy | Users with limited digital literacy (a named PMMS constraint per [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)) can complete core tasks |
| Shared-device use | Multiple staff sharing one device can each work efficiently within the sign-in/sign-out model |
| Field conditions | The mobile experience holds up under real venue conditions (sun glare, gloves, one-handed use) |
| Time pressure | Time-sensitive workflows (score entry between events) remain usable under real time constraints |

Usability validation is human-facilitated (Q3, per [quality-engineering-strategy.md, Section 6](quality-engineering-strategy.md#6-test-quadrants)) — it cannot be fully automated, and is scheduled deliberately rather than treated as an incidental byproduct of feature testing.

## 3. Compatibility Testing

| Category | Scope |
|---|---|
| Supported browsers | The administrative and public web apps are tested across the browsers PMMS commits to supporting (specific list is an evidence-based, not assumed, decision) |
| Desktop sizes | Common desktop viewport sizes |
| Tablet sizes | Common tablet viewport sizes, relevant for committee/venue-station use |
| Mobile sizes | Common mobile viewport sizes, relevant for the public portal and any mobile-web use |
| Android versions | The range of Android versions the Flutter app commits to supporting |
| Device classes | Low-end through high-end device classes, given resource-constrained venue conditions are a named constraint |
| Scanner hardware | Specific scanner hardware models, once selected |
| Kiosk displays | Public kiosk display hardware, once selected |
| Scoreboard displays | Public scoreboard display hardware, once selected |
| Slow networks | Degraded-bandwidth conditions, a named PMMS constraint |
| Intermittent networks | Frequently-dropping connections, relevant to venue conditions |
| High-latency links | Distant/poor-infrastructure venue connections |
| Low-memory devices | Budget-class Android devices likely to be issued to field staff |

**Do not finalize a compatibility matrix without evidence** — restated absolutely; the specific browser/OS/device-version list PMMS commits to supporting is determined from real device-inventory and analytics data (once available), never assumed upfront from generic "modern browser" guesses.

## 4. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably the specific accessibility-conformance target level, and when a real device-compatibility matrix can be established (dependent on DepEd's actual field-device inventory).
