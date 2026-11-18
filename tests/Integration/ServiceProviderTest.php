<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\Addon;

/**
 * The add-on boots inside a minimal Statamic.
 *
 * @command  vendor/bin/pest tests/Integration/ServiceProviderTest.php
 */
it('is discovered by Statamic as an add-on', function (): void {
    // Expect the add-on among those Statamic booted
    expect(Addon::all()->map->package()->all())->toContain('uams-web/aether-enums');
});

it('registers all nine generator commands', function (): void {
    // Read the command names Artisan knows from the add-on's provider alone
    $registered = array_keys(Artisan::all());

    expect($registered)->toContain(
        'generate:county-enum',
        'generate:credential-registry-enum',
        'generate:international-code-designator-enum',
        'generate:isced-enums',
        'generate:isco-08-enum',
        'generate:mime-type-enum',
        'generate:schema-type-enum',
        'generate:state-enum',
        'generate:zip-enum',
    );
});
