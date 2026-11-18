<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\CanadianProvince;

/**
 * Behavior of UAMSWeb\Aether\Enums\CanadianProvince: full English names and postal
 * abbreviations (the postal abbreviation is the backing string value). 100%
 * mutation score on UAMSWeb\Aether\Enums\CanadianProvince (2 killable mutants).
 *
 * @command  php artisan test --compact tests/Unit/Enums/CanadianProvinceTest.php
 */
it('returns the full name for sample provinces', function (): void {
    expect(CanadianProvince::AB->fullName())->toBe('Alberta');
    expect(CanadianProvince::BC->fullName())->toBe('British Columbia');
    expect(CanadianProvince::QC->fullName())->toBe('Quebec');
    expect(CanadianProvince::NL->fullName())->toBe('Newfoundland and Labrador');
});

it('returns the postal abbreviation matching the backing value', function (): void {
    expect(CanadianProvince::ON->postal())->toBe('ON');
    expect(CanadianProvince::ON->postal())->toBe(CanadianProvince::ON->value);
});

it('exposes a non-empty full name for every case', function (): void {
    foreach (CanadianProvince::cases() as $province) {
        expect($province->fullName())->not->toBe('');
    }
});
