<?php

test('clarifi defaults target chile and clp', function () {
    $environmentExample = file_get_contents(base_path('.env.example'));

    expect(config('clarifi.default_currency'))->toBe('CLP')
        ->and(config('app.timezone'))->toBe('America/Santiago')
        ->and($environmentExample)->toContain('APP_FAKER_LOCALE=es_CL')
        ->toContain('APP_TIMEZONE=America/Santiago')
        ->toContain('CLARIFI_DEFAULT_CURRENCY=CLP');
});
