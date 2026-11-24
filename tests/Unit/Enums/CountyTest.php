<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\County;
use UAMSWeb\Aether\Enums\CountyInfo;

covers(County::class);

/**
 * Behavior of UAMSWeb\Aether\Enums\County getters: the full name, the three-digit
 * county FIPS code and the parent state's USPS code (#65).
 *
 * @command  php artisan test --compact tests/Unit/Enums/CountyTest.php
 */
it('returns the parent USPS code for a sample county', function (): void {
    expect(County::AR_BENTON_COUNTY->parent())->toBe('AR');
    expect(County::AR_BENTON_COUNTY->fullName())->toBe('Benton County');
    expect(County::AR_BENTON_COUNTY->fipsCode())->toBe('007');
});

it('returns the parent USPS code its attribute holds for every case', function (): void {
    foreach (County::cases() as $county) {
        $info = new ReflectionEnumUnitCase(County::class, $county->name)
            ->getAttributes(CountyInfo::class)[0]
            ->newInstance();

        expect($county->parent())->toBe($info->parentUspsCode);
        expect($county->parent())->toMatch('/^[A-Z]{2}$/');
    }
});

it('returns a three-digit county FIPS code for every case', function (): void {
    foreach (County::cases() as $county) {
        expect($county->fipsCode())->toMatch('/^\d{3}$/');
    }
});
