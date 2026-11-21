<?php

declare(strict_types=1);

use Pest\Mutate\Support\FileFinder;
use Symfony\Component\Yaml\Yaml;

/**
 * The generated enums stay out of the mutation scope (UAMS-Web/aether-enums#22): a mutation run
 * over the largest of them never finishes. Three places carry the list, and they must agree:
 * the `phpunit.xml` exclude list (the source), the ignore list `tests/Pest.php` hands to Pest, and
 * the `:(exclude)` pathspecs in the `mutate-paths` input of `ci.yml`.
 *
 * @command  vendor/bin/pest tests/Unit/MutationScopeTest.php
 */

require_once __DIR__.'/../Support/MutationScope.php';

it('reads the generated enum list from phpunit.xml', function (): void {
    $files = generatedEnumFiles();

    expect($files)->toHaveCount(19)
        ->and($files)->toContain('src/ZipCode.php', 'src/Country.php')
        ->and($files)->each->toStartWith('src/');

    foreach ($files as $file) {
        expect(dirname(__DIR__, 2).'/'.$file)->toBeFile();
    }
});

it('keeps every generated enum out of a mutation run over src', function (): void {
    $root = dirname(__DIR__, 2);
    $previous = getcwd();
    chdir($root);

    try {
        $paths = [];

        foreach (FileFinder::files(['src'], mutationIgnoredPaths()) as $file) {
            $paths[] = str_replace('\\', '/', substr($file->getRealPath(), strlen($root) + 1));
        }
    } finally {
        if ($previous !== false) {
            chdir($previous);
        }
    }

    expect($paths)->not->toBeEmpty()
        ->and($paths)->toContain('src/Generation/GeneratorUtils.php', 'src/ServiceProvider.php')
        ->and(array_intersect($paths, generatedEnumFiles()))->toBe([]);
});

it('finds a generated enum when nothing is ignored, so the exclusion check can fail', function (): void {
    $root = dirname(__DIR__, 2);
    $previous = getcwd();
    chdir($root);

    try {
        $paths = [];

        foreach (FileFinder::files(['src'], []) as $file) {
            $paths[] = str_replace('\\', '/', substr($file->getRealPath(), strlen($root) + 1));
        }
    } finally {
        if ($previous !== false) {
            chdir($previous);
        }
    }

    expect(array_intersect($paths, generatedEnumFiles()))->toHaveCount(19);
});

it('excludes the same files from the changed files ci.yml selects', function (): void {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/ci.yml');

    expect($workflow)->toBeArray();

    $jobs = is_array($workflow) && is_array($workflow['jobs'] ?? null) ? $workflow['jobs'] : [];
    $checks = is_array($jobs['checks'] ?? null) ? $jobs['checks'] : [];
    $inputs = is_array($checks['with'] ?? null) ? $checks['with'] : [];
    $mutatePathsInput = $inputs['mutate-paths'] ?? '';

    expect($mutatePathsInput)->toBeString();

    $mutatePaths = preg_split('/\s+/', trim(is_string($mutatePathsInput) ? $mutatePathsInput : '')) ?: [];

    $excluded = [];
    $included = [];

    foreach ($mutatePaths as $entry) {
        if (str_starts_with($entry, ':(exclude)')) {
            $excluded[] = substr($entry, strlen(':(exclude)'));
        } else {
            $included[] = $entry;
        }
    }

    sort($excluded);

    expect($included)->toBe(['src'])
        ->and($excluded)->toBe(generatedEnumFiles());
});
