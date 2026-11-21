<?php

declare(strict_types=1);

use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use UAMSWeb\Aether\Enums\Console\GenerateInternationalCodeDesignatorEnum;
use UAMSWeb\Aether\Enums\Generation\EnumPaths;

/**
 * The generator commands that run from files the add-on ships or from records handed
 * to them: `generate:isced-enums`, `generate:isco-08-enum --local` and
 * `generate:international-code-designator-enum` (UAMS-Web/aether-enums#5).
 *
 * Each run writes to a scratch directory in place of `src/`, so the committed enums
 * are never overwritten, and nothing reaches the network.
 *
 * @command  vendor/bin/pest tests/Integration/Console/GenerateOfflineEnumCommandsTest.php
 */
beforeEach(function (): void {
    // Send enum output to a scratch directory
    $this->scratch = sys_get_temp_dir().DIRECTORY_SEPARATOR.'aether-enums-cmd-'.Str::random(12);
    File::ensureDirectoryExists($this->scratch);
    useScratchEnumPaths($this->scratch);
});

afterEach(function (): void {
    // Restore `src/` and remove the scratch directory
    EnumPaths::useEnumDirectory(null);
    File::deleteDirectory($this->scratch);
});

/**
 * Register an ICD command that returns the given records instead of fetching them.
 *
 * @param  ?array<int, array{id: string, name: string, description: string}>  $records
 */
function offlineEnumCommandIcdWith(?array $records): void
{
    // The signature attribute is not inherited, so the subclass declares it again
    Artisan::registerCommand(new #[Signature('generate:international-code-designator-enum')] class($records) extends GenerateInternationalCodeDesignatorEnum
    {
        /**
         * @param  ?array<int, array{id: string, name: string, description: string}>  $records
         */
        public function __construct(private readonly ?array $records)
        {
            parent::__construct();
        }

        #[Override]
        protected function fetchRecords(): ?array
        {
            return $this->records;
        }
    });
}

it('writes the ICD enum under the add-on namespace from the records it is given', function (): void {
    offlineEnumCommandIcdWith([
        ['id' => '0007', 'name' => 'Organisationsnummer', 'description' => 'Swedish organization number'],
        ['id' => '0060', 'name' => 'Data Universal Numbering System', 'description' => ''],
    ]);

    $exit = Artisan::call('generate:international-code-designator-enum');
    $php = (string) file_get_contents(EnumPaths::enumFile('InternationalCodeDesignator.php'));

    expect($exit)->toBe(0)
        ->and($php)->toContain('namespace UAMSWeb\\Aether\\Enums;')
        ->and($php)->toContain("case ICD_0007 = '0007';")
        ->and($php)->toContain("case ICD_0060 = '0060';")
        ->and($php)->not->toContain('App\\');
});

it('fails and writes nothing when the ICD records cannot be fetched', function (): void {
    offlineEnumCommandIcdWith(null);

    $exit = Artisan::call('generate:international-code-designator-enum');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('Failed to fetch or parse ICD XML')
        ->and(is_file(EnumPaths::enumFile('InternationalCodeDesignator.php')))->toBeFalse();
});

it('writes the three ISCED enums from the bundled fixtures', function (): void {
    $exit = Artisan::call('generate:isced-enums');

    expect($exit)->toBe(0);

    foreach (['IscedA2011' => 66, 'IscedP2011' => 91, 'IscedF2013' => 216] as $enum => $expectedCases) {
        $php = (string) file_get_contents(EnumPaths::enumFile($enum.'.php'));

        expect($php)->toContain('namespace UAMSWeb\\Aether\\Enums;')
            ->and($php)->toContain('enum '.$enum.': string')
            ->and(substr_count($php, "\n    case "))->toBe($expectedCases)
            ->and($php)->not->toContain('App\\');
    }
});

it('writes the ISCO-08 enum from the bundled workbooks', function (): void {
    $exit = Artisan::call('generate:isco-08-enum', ['--local' => true]);
    $php = (string) file_get_contents(EnumPaths::enumFile('Isco08.php'));

    expect($exit)->toBe(0)
        ->and($php)->toContain('namespace UAMSWeb\\Aether\\Enums;')
        ->and($php)->toContain('enum Isco08: string')
        ->and(substr_count($php, "\n    case "))->toBe(619)
        ->and($php)->not->toContain('App\\');
});
