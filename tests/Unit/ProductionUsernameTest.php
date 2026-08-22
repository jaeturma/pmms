<?php

use App\Services\ProductionUsername;

test('production usernames use first name and surname with suffix', function (string $name, string $expected) {
    expect(app(ProductionUsername::class)->fromName($name))->toBe($expected);
})->with([
    ['JOANN S. BALIONG', 'joann.baliong'],
    ['CECILY LOURDS M. BARUIZ', 'cecily.baruiz'],
    ['EMMA RITA S. MENDOZA', 'emma.mendoza'],
    ['CRISTITO S. SUAN JR.', 'cristito.suanjr'],
    ['ARZIEL PAULINE JACKIE T. JAMORA', 'arziel.jamora'],
    ['NOLE VILLASON', 'nole.villason'],
]);
