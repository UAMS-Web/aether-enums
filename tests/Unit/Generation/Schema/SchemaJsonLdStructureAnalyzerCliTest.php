<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use UAMSWeb\Aether\Enums\Generation\Schema\SchemaJsonLdStructureAnalyzer;

/**
 * Subprocess coverage for the analyzer's command line, ported from the site's
 * `SchemaJsonLdStructureAnalyzerCliTest` when the analyzer moved into the
 * add-on (#8).
 *
 * The class file has no load-time code (UAMS-Web/uams-statamic-archive#1008):
 * autoloading or including it, as Rector, PHPStan and the test suite do, or
 * even executing it directly, only defines the class. The command line is
 * `scripts/analyze-schema-jsonld.php`, which calls `runCli()` with the class's
 * directory, and refuses to run where the add-on has no `vendor/` of its own,
 * as when it is installed in a host.
 *
 * The script is run only on paths that write nothing: a successful run writes
 * the committed reports in the class's directory, so the success paths are
 * covered in-process by `SchemaJsonLdStructureAnalyzerTest` instead.
 *
 * @command  vendor/bin/pest tests/Unit/Generation/Schema/SchemaJsonLdStructureAnalyzerCliTest.php
 */
$analyzerPath = dirname(__DIR__, 4).'/src/Generation/Schema/SchemaJsonLdStructureAnalyzer.php';
$scriptPath = dirname(__DIR__, 4).'/scripts/analyze-schema-jsonld.php';

it('defines the class and runs nothing when the file is included', function () use ($analyzerPath): void {
    // Include it while the process runs some other command, so argv[1] is e.g. "process"
    $script = sprintf(
        '$_SERVER["argv"] = ["some-tool", "process"]; require %s; echo class_exists(%s) ? "REACHED" : "NO_CLASS";',
        var_export($analyzerPath, true),
        var_export(SchemaJsonLdStructureAnalyzer::class, true),
    );

    $process = new Process([PHP_BINARY, '-r', $script]);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('REACHED')
        ->and($process->getErrorOutput())->toBe('');
});

it('runs nothing when the class file is executed directly', function () use ($analyzerPath): void {
    $process = new Process([PHP_BINARY, $analyzerPath, 'unused.jsonld']);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->toBe('');
});

it('exits 1 from the script, naming the path, when the input file does not exist', function () use ($scriptPath): void {
    $missing = sys_get_temp_dir().'/jsonld-analyzer-missing-'.Str::random(12).'.jsonld';

    $process = new Process([PHP_BINARY, $scriptPath, $missing]);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->toBe('Error: File not found or not readable: '.$missing.PHP_EOL);
});

it('exits 1 from the script with the parser message when the input is not valid JSON', function () use ($scriptPath): void {
    $input = sys_get_temp_dir().'/jsonld-analyzer-invalid-'.Str::random(12).'.jsonld';
    file_put_contents($input, '{');

    try {
        $process = new Process([PHP_BINARY, $scriptPath, $input]);
        $process->run();

        expect($process->getExitCode())->toBe(1)
            ->and($process->getOutput())->toBe('')
            ->and($process->getErrorOutput())->toBe("Error: Invalid JSON: Syntax error\n");
    } finally {
        unlink($input);
    }
});

it('refuses to run, writing nothing, where the add-on has no vendor directory of its own', function () use ($scriptPath): void {
    // Lay the script out as a host's Composer install does: the package directory holds no vendor/
    $package = sys_get_temp_dir().'/jsonld-analyzer-installed-'.Str::random(12);
    mkdir($package.'/scripts', 0777, true);
    copy($scriptPath, $package.'/scripts/analyze-schema-jsonld.php');

    // The script names the path PHP resolved, symlinks and all (macOS temp is under /private)
    $resolved = mustBeString(realpath($package), 'the scratch package directory');

    try {
        $process = new Process([PHP_BINARY, $package.'/scripts/analyze-schema-jsonld.php']);
        $process->run();

        expect($process->getExitCode())->toBe(1)
            ->and($process->getOutput())->toBe('')
            ->and($process->getErrorOutput())->toBe(
                'Error: '.$resolved."/vendor/autoload.php not found. Run this script from a checkout of uams-web/aether-enums after `composer install`; installed as a dependency it would write into the host's vendor/ directory.".PHP_EOL,
            )
            ->and(scandir($package))->toBe(['.', '..', 'scripts'])
            ->and(scandir($package.'/scripts'))->toBe(['.', '..', 'analyze-schema-jsonld.php']);
    } finally {
        unlink($package.'/scripts/analyze-schema-jsonld.php');
        rmdir($package.'/scripts');
        rmdir($package);
    }
});
