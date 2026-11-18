<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums\Attributes;

use Attribute;

/**
 * Attribute carrying Schema.org DataType metadata for a SchemaDataType enum case.
 *
 * @see src/Generation/Schema/schema-jsonld-normalization.md
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class SchemaDataTypeInfo
{
    /**
     * @param  string|null  $rdfs_comment  DataType description (rdfs:comment), HTML with links
     */
    public function __construct(
        public ?string $rdfs_comment = null,
    ) {}
}
