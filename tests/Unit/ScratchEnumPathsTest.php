<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\EnumPaths;

/**
 * The guard the generator tests call before running a generator (UAMS-Web/aether-enums#23): it
 * redirects `EnumPaths` to a scratch directory and fails, before anything is written, when the
 * redirect did not take.
 *
 * @command  vendor/bin/pest tests/Unit/ScratchEnumPathsTest.php
 */
afterEach(function (): void {
    EnumPaths::useEnumDirectory(null);
    EnumPaths::useGenerationDirectory(null);
});

it('redirects enum files and bundled fixtures to the given directories', function (): void {
    $scratch = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scratch-enum-paths';

    useScratchEnumPaths($scratch.DIRECTORY_SEPARATOR.'Enums', $scratch.DIRECTORY_SEPARATOR.'Generation');

    expect(EnumPaths::enumDirectory())->toBe($scratch.DIRECTORY_SEPARATOR.'Enums')
        ->and(EnumPaths::enumFile('State.php'))->toBe($scratch.DIRECTORY_SEPARATOR.'Enums'.DIRECTORY_SEPARATOR.'State.php')
        ->and(EnumPaths::generationDirectory('Isco08'))->toBe($scratch.DIRECTORY_SEPARATOR.'Generation'.DIRECTORY_SEPARATOR.'Isco08');
});

it('leaves the bundled fixtures alone when only the enum directory is redirected', function (): void {
    $scratch = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scratch-enum-paths';

    useScratchEnumPaths($scratch);

    expect(EnumPaths::enumFile('Isco08.php'))->toBe($scratch.DIRECTORY_SEPARATOR.'Isco08.php')
        ->and(EnumPaths::generationDirectory('Isco08'))->toBe(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Generation'.DIRECTORY_SEPARATOR.'Isco08');
});
