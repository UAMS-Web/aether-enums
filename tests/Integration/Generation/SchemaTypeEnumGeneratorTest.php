<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use UAMSWeb\Aether\Enums\Generation\Schema\SchemaTypeEnumGenerator;

/**
 * Formatting of the four files SchemaTypeEnumGenerator
 * (src/Generation/Schema/SchemaTypeEnumGenerator.php) writes, rendered
 * from the schema.org JSON-LD committed beside it so no network is involved
 * (UAMS-Web/uams-statamic#722).
 *
 * The generated files must be Pint-clean with no separate pass. That holds
 * without running Pint here: the test below requires each generated file to
 * match the committed enum byte for byte apart from its date, and
 * `composer test:pint` checks the committed enums on every gate run. Running
 * Pint over the 2.2 MB of generated PHP here as well cost most of the suite's
 * time for no extra guarantee.
 *
 * @command  php artisan test --compact tests/Unit/Services/EnumGeneration/SchemaTypeEnumGeneratorTest.php
 */

/**
 * Run the generator over the committed JSON-LD and return each file it writes, keyed by enum name.
 *
 * @return array<string, string>
 */
function schemaEnumGeneratedFiles(): array
{
    $root = dirname(__DIR__, 3);
    $jsonLd = mustBeString(file_get_contents($root.'/src/Generation/Schema/schemaorg-current-https.jsonld'), 'the committed schema.org JSON-LD');

    /** @var array<string, string> $written */
    $written = [];
    File::partialMock()->shouldReceive('put')->times(4)->andReturnUsing(static function (string $path, string $php) use (&$written): int {
        $written[pathinfo($path, PATHINFO_FILENAME)] = $php;

        return \strlen($php);
    });

    new SchemaTypeEnumGenerator(static fn (string $url): string => $jsonLd)->generate();
    ksort($written);

    return $written;
}

it('regenerates each committed enum byte for byte apart from its generation date (#722)', function (): void {
    $root = dirname(__DIR__, 3);
    $files = schemaEnumGeneratedFiles();

    // The docblock records the day the file was generated; everything else must match
    $undated = static fn (string $php): string => (string) preg_replace('/on \d{4}-\d{2}-\d{2} using/', 'on <date> using', $php, 1);

    foreach ($files as $name => $php) {
        $committed = mustBeString(file_get_contents($root.'/src/'.$name.'.php'), 'the committed '.$name.' enum');

        expect($undated($php))->toBe($undated($committed));
    }

    expect(array_keys($files))->toBe(['SchemaDataType', 'SchemaEnumerationMember', 'SchemaProperty', 'SchemaType']);
});
