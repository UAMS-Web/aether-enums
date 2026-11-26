<?php

declare(strict_types=1);

use Pest\Mutate\Support\FileFinder;
use Symfony\Component\Yaml\Yaml;
use UAMSWeb\Aether\Enums\County;
use UAMSWeb\Aether\Enums\Generation\GeneratorUtils;
use UAMSWeb\Aether\Enums\Generation\Schema\SchemaJsonLdStructureAnalyzer;
use UAMSWeb\Aether\Enums\Language;

/**
 * The generated enums stay out of the mutation scope (UAMS-Web/aether-enums#22): a mutation run
 * over the largest of them never finishes. Three places carry the list, and they must agree:
 * the `phpunit.xml` exclude list (the source), the ignore list `tests/Pest.php` hands to Pest, and
 * the `:(exclude)` pathspecs in the `mutate-paths` input of `ci.yml`.
 *
 * No test file may name one of those enums in `covers()` either (UAMS-Web/aether-enums#97): with
 * coverage collected, PHPUnit warns that the class is not a valid coverage target, and
 * `failOnWarning` ends the run, so a coverage or mutation run fails before it mutates anything.
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

it('resolves the classes a covers() call names through the file imports', function (): void {
    $code = <<<'PHP'
        <?php

        use UAMSWeb\Aether\Enums\County;
        use UAMSWeb\Aether\Enums\Generation as Gen;

        covers(County::class, Gen\GeneratorUtils::class, \UAMSWeb\Aether\Enums\Language::class);

        it('passes', function () use ($x): void {
            $this->covers(Unrelated::class);
        });
        PHP;

    expect(coversTargets($code))->toBe([
        County::class,
        GeneratorUtils::class,
        Language::class,
    ]);
});

it('reports a covers() target on the phpunit.xml exclude list, so the next check can fail', function (): void {
    $file = new ReflectionClass(County::class)->getFileName();

    expect($file)->toBeString()
        ->and(generatedEnumFiles())->toContain(str_replace('\\', '/', substr((string) $file, strlen(dirname(__DIR__, 2)) + 1)));
});

it('names no generated enum in any covers() call, which would fail every coverage and mutation run (#97)', function (): void {
    $root = dirname(__DIR__, 2);
    $targets = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/tests', FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        foreach (coversTargets((string) file_get_contents($file->getPathname())) as $class) {
            $targets[$class] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        }
    }

    expect($targets)->toHaveKey(SchemaJsonLdStructureAnalyzer::class);

    $offending = [];

    foreach ($targets as $class => $test) {
        // A name the reader could not resolve would otherwise pass unchecked.
        if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
            $offending[] = "{$test} covers {$class}, which does not resolve to a class";

            continue;
        }

        $path = str_replace('\\', '/', substr((string) new ReflectionClass($class)->getFileName(), strlen($root) + 1));

        if (in_array($path, generatedEnumFiles(), true)) {
            $offending[] = "{$test} covers {$class} ({$path})";
        }
    }

    expect($offending)->toBe([]);
});
