<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums;

use Attribute;

/**
 * Attribute for storing province and territory names on enum cases.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class CanadianProvinceInfo
{
    public function __construct(public string $fullName) {}
}
