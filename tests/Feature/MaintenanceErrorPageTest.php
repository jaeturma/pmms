<?php

test('the server error page suggests correcting linked data instead of displaying a raw error', function () {
    $html = view('errors.500')->render();

    expect($html)
        ->toContain('/favicon.svg')
        ->toContain('Information needs attention')
        ->toContain('We’ll be back shortly.')
        ->toContain('delegation, school, municipality, sport, event entry, and team membership')
        ->toContain('Try again');
});

test('the maintenance response uses the same branded fallback', function () {
    expect(view('errors.503')->render())
        ->toContain('/favicon.svg')
        ->toContain('Error 503')
        ->toContain('Service temporarily unavailable');
});
