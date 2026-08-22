<?php

/* Production fixture transcribed from Venues.xlsx. `areas` are included only
 * when both quantity and physical ownership are clear. */
return [
    ['sport' => 'ARCHERY', 'area_text' => '30M X 100M AREA', 'venues' => [
        ['name' => 'Rear of Superhealth Center, Maparat', 'source' => 'AT THE BACK OF SUPERHEALTH CENTER, MAPARAT', 'areas' => ['playing_area', 1, 'Playing Area'], 'coordinators' => [['Jovir Amora', '09107159999']]],
    ]],
    ['sport' => 'ARNIS', 'area_text' => '1 GYM', 'status' => 'ambiguous', 'notes' => 'One required gym but two venues are named; confirm authoritative venue.', 'venues' => [
        ['name' => 'CNHS Gym', 'source' => 'CNHS GYM'],
        ['name' => 'Osmeña ES Gym', 'source' => 'OSMENA ES GYM', 'coordinators' => [['Lilibeth Relampagos', '09077320025']]],
    ]],
    ['sport' => 'ATHLETICS', 'area_text' => '100 HURDLES', 'venues' => [
        ['name' => 'Oval', 'source' => 'OVAL', 'internal_notes' => 'MATERIALS C/O SDO', 'areas' => ['track', 1, 'Track'], 'coordinators' => [['Wendell Sanchez', null]]],
    ]],
    ['sport' => 'BADMINTON', 'area_text' => '4 COURTS', 'venues' => [
        ['name' => 'Oval New Court', 'source' => 'OVAL NEW COURT', 'areas' => ['court', 2, 'Court'], 'coordinators' => [['Judyland Yu', '09287142105'], ['Victor Salinas', null]]],
        ['name' => 'Oval Old Court', 'source' => 'OVAL OLD COURT', 'areas' => ['court', 2, 'Court']],
    ]],
    ['sport' => 'BASEBALL', 'area_text' => '2 DIAMONDS', 'venues' => [
        ['name' => 'Luzano Lot', 'source' => 'LUZANO LOT (BESIDE MAPARAT PUBLIC CEMETERY)', 'address' => 'Beside Maparat Public Cemetery', 'areas' => ['diamond', 2, 'Diamond'], 'coordinators' => [['Jumar Ian Teves', '09776630595']]],
    ]],
    ['sport' => 'BASKETBALL', 'area_text' => '4 COVERED COURTS', 'venues' => [
        ['name' => 'Municipal Gym', 'source' => '1 MUN GYM', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Saldy Caballero', null]]],
        ['name' => 'Brgy Gym San Jose', 'source' => 'BRGY GYM SAN JOSE', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Carlos Devila', '09477419938']]],
        ['name' => 'Brgy Gym Poblacion', 'source' => '1 BRGY GYM POBLACION', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Antonio Baste', null]]],
        ['name' => 'Brgy Gym Osmeña', 'source' => '1 BRGY GYM OSMENA', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Selang Cuyos', null]]],
    ]],
    ['sport' => 'BILLIARDS', 'area_text' => '6 TABLES', 'venues' => [
        ['name' => 'Legacy Gym', 'source' => 'LEGACY GYM', 'areas' => ['table', 6, 'Table'], 'coordinators' => [['Aquilino Camus', null]]],
    ]],
    ['sport' => 'BOXING', 'area_text' => '1 GYM', 'venues' => [
        ['name' => 'Municipal Pickleball Court', 'source' => 'MUNICIPAL PICKLEBALL COURT', 'areas' => ['ring', 1, 'Ring'], 'coordinators' => [['Oliver TuyOr', null]]],
    ]],
    ['sport' => 'CHESS', 'area_text' => '2 AIRCON', 'venues' => [
        ['name' => 'Congressional Session Hall', 'source' => '1 CONG SESSION HALL ELEMENTARY', 'areas' => ['room', 1, 'Elementary Room'], 'coordinators' => [['Marivi Morabe', null]]],
        ['name' => 'Parish Mess Hall', 'source' => 'PARISH MESS HALL - SECONDARY', 'areas' => ['room', 1, 'Secondary Room'], 'coordinators' => [['Rosie Villamor', null]]],
    ]],
    ['sport' => 'DANCESPORT', 'area_text' => '1 GYM', 'venues' => [
        ['name' => 'Assumption Gym', 'source' => 'ASSUMPTION GYM', 'areas' => ['gym', 1, 'Gym Floor'], 'coordinators' => [['Celia Sala', null]]],
    ]],
    ['sport' => 'FOOTBALL', 'area_text' => '2 FOOTBALL FIELDS', 'venues' => [
        ['name' => 'Oval', 'source' => '1 OVAL', 'areas' => ['field', 1, 'Football Field'], 'coordinators' => [['Reynaldo Catienza', null]]],
        ['name' => 'Near Tower Field', 'source' => 'NEAR TOWER', 'areas' => ['field', 1, 'Football Field'], 'coordinators' => [['Suzette Sayson', null]]],
    ]],
    ['sport' => 'FUTSAL', 'area_text' => '1 GYM', 'status' => 'needs_review', 'notes' => 'SOURCE_REVIEW_REQUIRED: unexplained source value 9696495732; not treated as a phone number.', 'venues' => [
        ['name' => 'Brgy San Miguel Gym', 'source' => 'BRGY SAN MIGUEL GYM', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Ramil Niones', null]]],
    ]],
    ['sport' => 'GYMNASTICS', 'area_text' => '3 GYM', 'status' => 'ambiguous', 'notes' => 'Three gyms required but only Brgy Gym Gabi is identified.', 'venues' => [
        ['name' => 'Brgy Gym Gabi', 'source' => 'BRGY GYM GABI', 'expected' => 3, 'coordinators' => [['Alma Soriano', null]]],
    ]],
    ['sport' => 'PARA_BOCCE', 'area_text' => '1 COURT', 'venues' => [
        ['name' => 'Compostela NHS', 'source' => 'Compostela NHS', 'areas' => ['court', 1, 'Bocce Court'], 'coordinators' => [['Sandy G. Yee', null]]],
    ]],
    ['sport' => 'PARA_GOALBALL', 'area_text' => '1 GYM', 'venues' => [
        ['name' => 'San Jose ES Gym', 'source' => 'SAN JOSE ES GYM', 'areas' => ['court', 1, 'Main Court'], 'internal_notes' => 'RECOMMEND FOR FLOOR RUBBERIZING', 'readiness' => 'needs_attention', 'coordinators' => [['Marilyn Celada', null]]],
    ]],
    ['sport' => 'PENCAK_SILAT', 'area_text' => '1 ROOM', 'venues' => [
        ['name' => 'New Alegria Gym', 'source' => 'NEW ALEGRIA GYM', 'areas' => ['room', 1, 'Competition Room'], 'coordinators' => [['Medelito Morabe', null]]],
    ]],
    ['sport' => 'SEPAK_TAKRAW', 'area_text' => '4 COURTS', 'venues' => [
        ['name' => 'Sports Complex', 'source' => 'SPORTS COMPLEX COURTS', 'areas' => ['court', 4, 'Court'], 'coordinators' => [['Dennis Suarez', null], ['Rouge Dela Torre', null]]],
    ]],
    ['sport' => 'SOFTBALL', 'area_text' => '2 DIAMONDS', 'venues' => [
        ['name' => 'Tent City', 'source' => 'TENT CITY', 'areas' => ['diamond', 2, 'Diamond'], 'coordinators' => [['Oscar Blasé', null], ['Rosalino Pogoy', null]]],
    ]],
    ['sport' => 'SWIMMING', 'area_text' => null, 'status' => 'needs_review', 'notes' => 'NEEDS_VENUE_CONFIRMATION: source gives Pantukan but no facility name.', 'venues' => [
        ['name' => 'Pantukan — Venue To Be Confirmed', 'source' => 'PANTUKAN', 'readiness' => 'for_validation'],
    ]],
    ['sport' => 'TABLE_TENNIS', 'area_text' => '8 TABLES', 'venues' => [
        ['name' => 'Tamia Brgy Gym', 'source' => 'TAMIA BRGY GYM', 'areas' => ['table', 8, 'Table'], 'coordinators' => [['Ivy Grajo', null], ['Virgie Arbitrario', null]]],
    ]],
    ['sport' => 'TAEKWONDO', 'area_text' => '4 COURTS', 'venues' => [
        ['name' => 'DDOSC', 'source' => 'DDOSC', 'areas' => ['court', 4, 'Court'], 'coordinators' => [['Josette Asilo', null], ['Marissa Juntilla', null]]],
    ]],
    ['sport' => 'TENNIS', 'area_text' => '4 COURTS', 'status' => 'ambiguous', 'notes' => 'Four courts across two venues without a stated distribution.', 'venues' => [
        ['name' => 'Sports Complex', 'source' => '1 SPORTS COMPLEX-ELEMENTARY', 'expected' => 4, 'coordinators' => [['Cynthia Vasquez', null]]],
        ['name' => 'Parish Hall', 'source' => 'PARISH HALL-SECONDARY', 'expected' => 4, 'coordinators' => [['Ramelyn Masiga', null]]],
    ]],
    ['sport' => 'VOLLEYBALL', 'area_text' => '4 COVERED COURTS', 'venues' => [
        ['name' => 'Sports Complex', 'source' => '2 COURTS SPORTS COMPLEX', 'areas' => ['court', 2, 'Court'], 'coordinators' => [['Cyril Estrada', null]]],
        ['name' => 'P6 Gym', 'source' => 'P6 GYM', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Ronan Ayco', null]]],
        ['name' => 'P7 Gym', 'source' => 'P7 GYM', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Eleuterio Saberon', null]]],
    ]],
    ['sport' => 'WEIGHTLIFTING', 'area_text' => '1 GYM', 'venues' => [
        ['name' => 'Congressional Gym', 'source' => 'CONG GYM', 'areas' => ['gym', 1, 'Competition Floor'], 'coordinators' => [['Ligaya Ang', null]]],
    ]],
    ['sport' => 'WRESTLING', 'area_text' => '1 COVERED COURT', 'venues' => [
        ['name' => 'DDOSC', 'source' => 'DDOSC', 'areas' => ['playing_area', 1, 'Wrestling Mat Area'], 'coordinators' => [['Edwin Remoreras', null]]],
    ]],
    ['sport' => 'WUSHU', 'area_text' => '1 COVERED COURT', 'venues' => [
        ['name' => 'Compostela Christian', 'source' => 'COMP CHRISTIAN', 'areas' => ['court', 1, 'Main Court'], 'coordinators' => [['Cresencia Lamoste', null]]],
    ]],
];
