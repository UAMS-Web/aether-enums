<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use UAMSWeb\Aether\Enums\Generation\EnumPaths;
use UAMSWeb\Aether\Enums\Generation\Isced\IscedF2013EnumGenerator;
use UAMSWeb\Aether\Enums\Generation\Isco08\Isco08EnumGenerator;

/**
 * The output, exit codes and option handling of `generate:isco-08-enum` and
 * `generate:isced-enums` (UAMS-Web/aether-enums#29). The enum files they write go to a scratch
 * directory, and nothing reaches the network.
 *
 * @command  vendor/bin/pest tests/Integration/Console/GenerateIscoAndIscedCommandsTest.php
 */
beforeEach(function (): void {
    $this->scratch = sys_get_temp_dir().DIRECTORY_SEPARATOR.'aether-enums-isco-'.Str::random(12);
    File::ensureDirectoryExists($this->scratch);
    useScratchEnumPaths($this->scratch);
});

afterEach(function (): void {
    EnumPaths::useEnumDirectory(null);
    EnumPaths::useGenerationDirectory(null);
    File::deleteDirectory($this->scratch);
});

it('reports the local fixtures and the written file for generate:isco-08-enum --local', function (): void {
    $exit = Artisan::call('generate:isco-08-enum', ['--local' => true]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'Using local fixtures under src/Generation/Isco08/'.PHP_EOL
            .'Wrote '.EnumPaths::enumFile('Isco08.php').PHP_EOL
        );
});

it('lists the expected fixture files and fails when the local ones are missing', function (): void {
    $generation = $this->scratch.DIRECTORY_SEPARATOR.'no-fixtures';
    useScratchEnumPaths($this->scratch, $generation);
    $paths = Isco08EnumGenerator::defaultLocalFixturePaths();

    $exit = Artisan::call('generate:isco-08-enum', ['--local' => true]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'Local fixtures not found or not readable. Expected files:'.PHP_EOL
            .'  [definitions] '.$paths['definitions'].PHP_EOL
            .'  [index] '.$paths['index'].PHP_EOL
        )
        ->and(is_file(EnumPaths::enumFile('Isco08.php')))->toBeFalse();
});

it('fails naming an unreadable --definitions file, before touching the index', function (): void {
    $missing = $this->scratch.DIRECTORY_SEPARATOR.'missing-definitions.xlsx';

    $exit = Artisan::call('generate:isco-08-enum', ['--definitions' => $missing, '--index' => $missing]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'definitions file is not readable: '.$missing.PHP_EOL
        );
});

it('fails naming an unreadable --index file', function (): void {
    $definitions = Isco08EnumGenerator::defaultLocalFixturePaths()['definitions'];
    $missing = $this->scratch.DIRECTORY_SEPARATOR.'missing-index.xlsx';

    $exit = Artisan::call('generate:isco-08-enum', ['--definitions' => $definitions, '--index' => $missing]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'index file is not readable: '.$missing.PHP_EOL
        );
});

it('generates from readable --definitions and --index files', function (): void {
    $paths = Isco08EnumGenerator::defaultLocalFixturePaths();

    $exit = Artisan::call('generate:isco-08-enum', ['--definitions' => $paths['definitions'], '--index' => $paths['index']]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'Wrote '.EnumPaths::enumFile('Isco08.php').PHP_EOL
        );
});

it('reports a workbook the generator rejects and exits 1', function (): void {
    // The index workbook has no ISCO code column in the definitions layout
    $index = Isco08EnumGenerator::defaultLocalFixturePaths()['index'];

    $exit = Artisan::call('generate:isco-08-enum', ['--definitions' => $index, '--index' => $index]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCO-08 enum from ILO workbooks...'.PHP_EOL
            .'Definitions workbook missing ISCO code column: '.$index.PHP_EOL
        )
        ->and(is_file(EnumPaths::enumFile('Isco08.php')))->toBeFalse();
});

it('reports the bundled fixture and the three written files for generate:isced-enums', function (): void {
    $exit = Artisan::call('generate:isced-enums');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toBe(
            'Generating ISCED enums (A 2011, P 2011, F 2013)...'.PHP_EOL
            .'Using bundled ISCED-F fixture: '.IscedF2013EnumGenerator::defaultFixturePath().PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedA2011.php').PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedP2011.php').PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedF2013.php').PHP_EOL
        );
});

it('treats an empty --isced-f as the bundled fixture', function (): void {
    $exit = Artisan::call('generate:isced-enums', ['--isced-f' => '']);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('Using bundled ISCED-F fixture: ');
});

it('uses a readable --isced-f file and says so', function (): void {
    $fixture = IscedF2013EnumGenerator::defaultFixturePath();

    $exit = Artisan::call('generate:isced-enums', ['--isced-f' => $fixture]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toBe(
            'Generating ISCED enums (A 2011, P 2011, F 2013)...'.PHP_EOL
            .'Using ISCED-F fixture: '.$fixture.PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedA2011.php').PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedP2011.php').PHP_EOL
            .'Wrote '.EnumPaths::enumFile('IscedF2013.php').PHP_EOL
        );
});

it('fails naming an unreadable --isced-f file and writes nothing', function (): void {
    $missing = $this->scratch.DIRECTORY_SEPARATOR.'missing-isced-f.json';

    $exit = Artisan::call('generate:isced-enums', ['--isced-f' => $missing]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCED enums (A 2011, P 2011, F 2013)...'.PHP_EOL
            .'ISCED-F JSON file is not readable: '.$missing.PHP_EOL
        )
        ->and(File::files($this->scratch))->toBe([]);
});

it('reports an ISCED-F file the generator rejects and exits 1', function (): void {
    $bad = $this->scratch.DIRECTORY_SEPARATOR.'bad-isced-f.json';
    file_put_contents($bad, '{"rows":"none"}');

    $exit = Artisan::call('generate:isced-enums', ['--isced-f' => $bad]);

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toBe(
            'Generating ISCED enums (A 2011, P 2011, F 2013)...'.PHP_EOL
            .'Using ISCED-F fixture: '.$bad.PHP_EOL
            ."ISCED-F fixture missing 'rows' array: ".$bad.PHP_EOL
        );
});
