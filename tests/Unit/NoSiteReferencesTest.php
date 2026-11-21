<?php

declare(strict_types=1);

/**
 * Nothing in the add-on depends on the site it was moved out of
 * (UAMS-Web/aether-enums#5): no `App\` class in `src/` or `tests/`, and no
 * application path helper, which would resolve inside whatever host runs it.
 *
 * @command  vendor/bin/pest tests/Unit/NoSiteReferencesTest.php
 */

/** An `App\` class reference, in code (`App\Foo`) or in generated-code strings (`App\\Foo`); never `UAMSWeb\App\`. */
const NO_SITE_REFERENCES_CLASS = '/(?<![\w\\\\])\\\\?App\\\\{1,2}[A-Za-z]/';

/** A call to a host application path helper. */
const NO_SITE_REFERENCES_PATH_HELPER = '/\b(?:app_path|base_path|resource_path|storage_path|database_path|public_path|config_path|lang_path)\s*\(/';

/**
 * The PHP files under the given directories, except this one (which names the patterns).
 *
 * @param  list<string>  $directories  Directories under the add-on root.
 * @return list<string>
 */
function noSiteReferencesFiles(array $directories = ['src', 'tests']): array
{
    $files = [];
    $root = dirname(__DIR__, 2);

    foreach ($directories as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php' && $file->getRealPath() !== __FILE__) {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

/**
 * The files whose contents match a pattern, relative to the add-on root.
 *
 * @param  list<string>  $directories  Directories under the add-on root.
 * @return list<string>
 */
function noSiteReferencesMatching(string $pattern, array $directories = ['src', 'tests']): array
{
    $root = dirname(__DIR__, 2);
    $matches = [];

    foreach (noSiteReferencesFiles($directories) as $file) {
        if (preg_match($pattern, mustBeString(file_get_contents($file), $file)) === 1) {
            $matches[] = substr($file, strlen($root) + 1);
        }
    }

    return $matches;
}

it('scans the add-on source and tests', function (): void {
    expect(noSiteReferencesFiles())->not->toBeEmpty()
        ->and(array_map(static fn (string $file): string => str_replace('\\', '/', $file), noSiteReferencesFiles()))
        ->toContain(str_replace('\\', '/', dirname(__DIR__, 2)).'/src/ServiceProvider.php');
});

it('detects an App class reference in code and in a generated-code string', function (string $source): void {
    expect(preg_match(NO_SITE_REFERENCES_CLASS, $source))->toBe(1);
})->with([
    'use statement' => ['use App\\Enums\\Country;'],
    'leading backslash' => ['$class = \\App\\Enums\\Country::class;'],
    'escaped string' => ['$out .= "use App\\\\Enums\\\\Country;\n";'],
]);

it('does not mistake the add-on namespace for an App reference', function (): void {
    expect(preg_match(NO_SITE_REFERENCES_CLASS, 'use UAMSWeb\\Aether\\Enums\\Country;'))->toBe(0)
        ->and(preg_match(NO_SITE_REFERENCES_CLASS, 'namespace UAMSWeb\\\\Aether\\\\Enums;'))->toBe(0);
});

it('references no App class', function (): void {
    expect(noSiteReferencesMatching(NO_SITE_REFERENCES_CLASS))->toBe([]);
});

it('calls no application path helper in the add-on source', function (): void {
    // The tests may use the Testbench application's paths; the source may not
    expect(noSiteReferencesMatching(NO_SITE_REFERENCES_PATH_HELPER, ['src']))->toBe([]);
});
