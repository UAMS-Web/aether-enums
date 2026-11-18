<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums\Generation\Schema;

/**
 * Warms a host's cache of entries by schema type after the schema type enum is regenerated.
 *
 * The add-on has no cache of its own to warm. A host that keeps one binds an
 * implementation of this contract in the container, and
 * {@see SchemaTypeEnumGenerator} calls it with the generated type names. With no
 * binding the generator skips the step.
 */
interface AliasCacheWarmer
{
    /**
     * Warm the cache for the generated schema type names.
     *
     * @param  list<string>  $typeNames  The schema type names in the regenerated enum.
     */
    public function warm(array $typeNames): void;
}
