<?php

test('the server error page shows a branded maintenance notice', function () {
    $html = view('errors.500')->render();

    expect($html)
        ->toContain('/favicon.svg')
        ->toContain('Temporary maintenance')
        ->toContain('We’ll be back shortly.')
        ->toContain('We apologize for the inconvenience')
        ->toContain('Try again');
});

test('the maintenance response uses the same branded fallback', function () {
    expect(view('errors.503')->render())
        ->toContain('/favicon.svg')
        ->toContain('Error 503')
        ->toContain('Service temporarily unavailable');
});
