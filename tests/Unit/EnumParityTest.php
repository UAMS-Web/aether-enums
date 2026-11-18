<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\EnumPaths;

/**
 * Every enum moved from the site keeps the cases and values it had there
 * (UAMS-Web/aether-enums#5).
 *
 * `tests/Fixtures/site-enum-manifest.json` was built from the site's own `app/Enums`
 * files at the commit the move was made from, not from the copies in `src/`: per enum
 * its backing type, case count, and a SHA-256 over its `NAME=value` lines. A changed
 * value, a dropped case or a new enum without a manifest entry fails here.
 *
 * @command  vendor/bin/pest tests/Unit/EnumParityTest.php
 */

/**
 * The manifest, keyed by enum short name.
 *
 * @return array<string, array{backing: string, cases: int, sha256: string}>
 */
function enumParityManifest(): array
{
    /** @var array<string, array{backing: string, cases: int, sha256: string}> */
    return json_decode(mustBeString(file_get_contents(__DIR__.'/../Fixtures/site-enum-manifest.json'), 'the enum manifest'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * The short names of the enums declared in `src/`.
 *
 * @return list<string>
 */
function enumParityDeclaredEnums(): array
{
    $names = [];

    foreach (glob(EnumPaths::enumDirectory().'/*.php') ?: [] as $file) {
        if (preg_match('/^enum\s+(\w+)\s*:/m', mustBeString(file_get_contents($file), $file), $match) === 1) {
            $names[] = $match[1];
        }
    }

    sort($names);

    return $names;
}

it('declares exactly the enums the manifest records', function (): void {
    $manifest = array_keys(enumParityManifest());
    sort($manifest);

    expect($manifest)->not->toBeEmpty()
        ->and(enumParityDeclaredEnums())->toBe($manifest);
});

it('resolves each enum under the add-on namespace with its site values', function (string $name, array $expected): void {
    $class = 'UAMSWeb\\Aether\\Enums\\'.$name;

    expect(enum_exists($class))->toBeTrue();

    /** @var class-string<BackedEnum> $class */
    $lines = [];

    foreach ($class::cases() as $case) {
        $lines[] = $case->name.'='.$case->value;
    }

    expect((string) new ReflectionEnum($class)->getBackingType())->toBe($expected['backing'])
        ->and(count($lines))->toBe($expected['cases'])
        ->and(hash('sha256', implode("\n", $lines)))->toBe($expected['sha256']);
})->with(function (): array {
    $cases = [];

    foreach (enumParityManifest() as $name => $entry) {
        $cases[$name] = [$name, $entry];
    }

    return $cases;
});
