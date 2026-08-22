# DdOPAA 2026 Playing Venues — Source Review

Source: `Venues.xlsx`, worksheet `Venues`. Extracted 2026-08-21. Source text is retained in the production fixture; normalized names below are initial editable values, not immutable truth.

| Sport | Source area requirement | Source venue text | Normalized venue(s) / areas | Source coordinator(s) | Contact found | Source notes | Import status | Review required |
|---|---|---|---|---|---|---|---|---|
| Archery | 30m × 100m area | At the back of Superhealth Center, Maparat | Rear of Superhealth Center, Maparat / Playing Area 1 | Jovir Amora | 09107159999 | — | READY_TO_SEED | No |
| Arnis | 1 gym | CNHS Gym / Osmeña ES Gym | CNHS Gym; Osmeña ES Gym; no area split created | Lilibeth Relampagos | 09077320025 | One required gym conflicts with two named gyms | AMBIGUOUS | Confirm which gym is authoritative |
| Athletics | 100 hurdles | Materials c/o SDO; Oval | Oval / Track | Wendell Sanchez | — | Materials c/o SDO | READY_TO_SEED | No |
| Badminton | 4 courts | Oval New Court (twice); Oval Old Court (twice) | Oval New Court / Courts 1–2; Oval Old Court / Courts 1–2 | Judyland Yu; Victor Salinas | 09287142105 | — | READY_TO_SEED | Confirm contact belongs to Judyland Yu |
| Baseball | 2 diamonds | Luzano Lot (beside Maparat Public Cemetery) | Luzano Lot / Diamonds 1–2 | Jumar Ian Teves | 09776630595 | — | READY_TO_SEED | No |
| Basketball | 4 covered courts | Municipal Gym; Brgy Gym San Jose; Brgy Gym Poblacion; Brgy Gym Osmeña | Four venues, Main Court at each | Saldy Caballero; Carlos Devila; Antonio Baste; Selang Cuyos | 09477419938 | Contact embedded with Carlos Devila | READY_TO_SEED | Confirm spelling of Devila |
| Billiards | 6 tables | Legacy Gym | Legacy Gym / Tables 1–6 | Aquilino Camus | — | — | READY_TO_SEED | No |
| Boxing | 1 gym | Municipal Pickleball Court | Municipal Pickleball Court / Playing Area 1 | Oliver TuyOr | — | — | READY_TO_SEED | Confirm venue suitability/name |
| Chess | 2 air-conditioned rooms | Congressional Session Hall (Elementary); Parish Mess Hall (Secondary) | Two venues / Room 1 each | Marivi Morabe; Rosie Villamor | — | Coordinators embedded in venue cells | READY_TO_SEED | Confirm “Congressional” expansion |
| Dancesports | 1 gym | Assumption Gym | Assumption Gym / Gym Floor | Celia Sala | — | — | READY_TO_SEED | No |
| Football | 2 football fields | Oval; Near Tower | Oval / Football Field; Near Tower Field / Football Field | Reynaldo Catienza; Suzette Sayson | — | Coordinators embedded | READY_TO_SEED | Confirm formal name of Near Tower field |
| Futsal | 1 gym | Brgy San Miguel Gym | Brgy San Miguel Gym / Main Court | Ramil Niones | `9696495732` in column B | SOURCE_REVIEW_REQUIRED; number not treated as phone | NEEDS_REVIEW | Identify numeric value |
| Gymnastics | 3 gyms | Brgy Gym Gabi | Brgy Gym Gabi; expected area count 3; no invented areas | Alma Soriano | — | Quantity/facility mismatch | AMBIGUOUS | Confirm remaining gyms or whether three areas are inside one gym |
| Paragames Bocce | 1 court | Compostela NHS | Compostela NHS / Bocce Court | Sandy G. Yee | — | — | READY_TO_SEED | No |
| Paragames Goalball | 1 gym | San Jose ES Gym | San Jose ES Gym / Main Court | Marilyn Celada | — | Recommend floor rubberizing | READY_TO_SEED | Readiness needs attention |
| Pencak Silat | 1 room | New Alegria Gym | New Alegria Gym / Competition Room | Medelito Morabe | — | — | READY_TO_SEED | No |
| Sepak Takraw | 4 courts | Sports Complex Courts | Sports Complex / Courts 1–4 | Dennis Suarez; Rouge Dela Torre | — | — | READY_TO_SEED | No |
| Softball | 2 diamonds | Tent City | Tent City / Diamonds 1–2 | Oscar Blasé; Rosalino Pogoy | — | — | READY_TO_SEED | Verify accented surname against personnel master |
| Swimming | Not stated | Pantukan | Unresolved Pantukan venue; no competition area | — | — | NEEDS_VENUE_CONFIRMATION | NEEDS_REVIEW | Actual pool/facility required |
| Table Tennis | 8 tables | Tamia Brgy Gym | Tamia Brgy Gym / Tables 1–8 | Ivy Grajo; Virgie Arbitrario | — | — | READY_TO_SEED | No |
| Taekwondo | 4 courts | DDOSC | DDOSC / Courts 1–4 | Josette Asilo; Marissa Juntilla | — | Shared physical venue with Wrestling | READY_TO_SEED | Confirm official DDOSC facility name |
| Tennis | 4 courts | Sports Complex (Elementary); Parish Hall (Secondary) | Two venues; expected total 4; no invented split | Cynthia Vasquez; Ramelyn Masiga | — | Coordinators embedded | AMBIGUOUS | Confirm court distribution |
| Volleyball | 4 covered courts | Sports Complex (2); P6 Gym; P7 Gym | Sports Complex / Courts 1–2; P6 Gym / Main Court; P7 Gym / Main Court | Cyril Estrada; Ronan Ayco; Eleuterio Saberon | — | — | READY_TO_SEED | No |
| Weightlifting | 1 gym | Congressional Gym | Congressional Gym / Competition Floor | Ligaya Ang | — | — | READY_TO_SEED | Confirm abbreviated source name “CONG GYM” |
| Wrestling | 1 covered court | DDOSC | Shared DDOSC venue / Wrestling Mat Area | Edwin Remoreras | — | Shared venue with Taekwondo | READY_TO_SEED | Confirm official DDOSC facility name |
| Wushu | 1 covered court | Compostela Christian | Compostela Christian / Main Court | Cresencia Lamoste | — | — | READY_TO_SEED | Confirm full facility name |

## Architecture mapping

- Reuse `venues` as the physical Playing Venue master.
- Add meet-specific `meet_sport_venues`; do not replace or delete legacy `event_venues` because existing data may depend on it.
- Add generic `competition_areas` owned by a venue.
- Add `game_coordinator_assignments` scoped to meet sport + venue and optionally an area.
- Add nullable `competition_area_id` to `event_schedules`; legacy schedule rows remain valid.
- Conflict validation uses competition area when supplied and falls back to whole-venue locking for legacy/unallocated schedules.

## Privacy and source handling

Coordinator numbers remain in private `source_contact_text`; they are not copied into public notes or public responses. The Futsal numeric value is retained only as source notes. Seeder identity uses stable `DDOPAA26-*` source codes and inserts missing records only, preserving later administrative edits.
