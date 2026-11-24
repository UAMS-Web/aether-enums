<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Language;
use UAMSWeb\Aether\Enums\LanguageInfo;

covers(Language::class);

/**
 * Behavior of the Language ISO 639 code getters: the code a case holds, or null
 * for a code it lacks, instead of a ValueError (#66, #70).
 *
 * @command  php artisan test --compact tests/Unit/Enums/LanguageCodeGettersTest.php
 */
it('returns the codes a language holds', function (): void {
    expect(Language::eng->iso639_1())->toBe('en');
    expect(Language::eng->iso639_2_b())->toBe('eng');
    expect(Language::eng->iso639_2_t())->toBe('eng');
    expect(Language::eng->iso639_3())->toBe('eng');
});

it('returns null for a code a language lacks', function (): void {
    expect(Language::eng->iso639_5())->toBeNull();
    expect(Language::aaa->iso639_1())->toBeNull();
});

it('returns the code its attribute holds, or null, for every case and getter', function (): void {
    $nulls = array_fill_keys(['iso639_1', 'iso639_2_b', 'iso639_2_t', 'iso639_3', 'iso639_5'], 0);

    foreach (Language::cases() as $language) {
        $info = new ReflectionEnumUnitCase(Language::class, $language->name)
            ->getAttributes(LanguageInfo::class)[0]
            ->newInstance();

        $actual = [
            'iso639_1' => $language->iso639_1(),
            'iso639_2_b' => $language->iso639_2_b(),
            'iso639_2_t' => $language->iso639_2_t(),
            'iso639_3' => $language->iso639_3(),
            'iso639_5' => $language->iso639_5(),
        ];

        foreach ($actual as $field => $value) {
            expect($value)->toBe($info->{$field});
            $nulls[$field] += $value === null ? 1 : 0;
        }
    }

    // Control: the data holds absent codes for each field, so the null branch ran.
    foreach ($nulls as $count) {
        expect($count)->toBeGreaterThan(0);
    }
});
