<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Attributes\SchemaTypeInfo;
use UAMSWeb\Aether\Enums\CountryInfo;
use UAMSWeb\Aether\Enums\IcdInfo;
use UAMSWeb\Aether\Enums\MimeTypeInfo;

/**
 * The defaults of the attribute classes the generated enums leave out when a source field is
 * absent (UAMS-Web/aether-enums#30). The enums are excluded from coverage and mutation, so these
 * constructors only run here.
 *
 * @command  vendor/bin/pest tests/Unit/InfoAttributeDefaultsTest.php
 */
it('gives a MimeTypeInfo no extensions and an empty category when they are omitted', function (): void {
    $info = new MimeTypeInfo('application/example');

    expect($info->mimeType)->toBe('application/example')
        ->and($info->extensions)->toBe([])
        ->and($info->category)->toBe('');
});

it('leaves the article off a CountryInfo name unless asked', function (): void {
    $default = new CountryInfo('Exampleland', 'EXL', '999');
    $withArticle = new CountryInfo('Exampleland', 'EXL', '999', true);

    expect($default->useThe)->toBeFalse()
        ->and($withArticle->useThe)->toBeTrue();
});

it('gives a SchemaTypeInfo no description, relations, slug or replacement when they are omitted', function (): void {
    $info = new SchemaTypeInfo;

    expect($info->rdfs_comment)->toBeNull()
        ->and($info->rdfs_sub_class_of)->toBe([])
        ->and($info->children)->toBe([])
        ->and($info->ancestors)->toBe([])
        ->and($info->descendants)->toBe([])
        ->and($info->property_names)->toBe([])
        ->and($info->slug)->toBe('')
        ->and($info->superseded_by)->toBeNull()
        ->and($info->members)->toBe([]);
});

it('gives an IcdInfo an empty description when it is omitted', function (): void {
    $info = new IcdInfo('0007', 'Organisationsnummer');

    expect($info->description)->toBe('');
});
