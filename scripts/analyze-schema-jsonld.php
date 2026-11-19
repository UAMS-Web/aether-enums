<?php

declare(strict_types=1);

/**
 * Analyze the structure of the schema.org JSON-LD vocabulary with
 * UAMSWeb\Aether\Enums\Generation\Schema\SchemaJsonLdStructureAnalyzer.
 *
 * Reads the given `.jsonld` file, or `schemaorg-current-https.jsonld` in the
 * analyzer's directory, and writes `schema-jsonld-structure-report.txt` (or
 * `.json` when `ANALYZE_OUTPUT=json`) and `schema-jsonld-all-ids.txt` into that
 * directory. Exits 1 when the input cannot be read or parsed, or the report
 * cannot be written.
 *
 * Run it from the root of a checkout of this add-on, after `composer install`:
 *
 *     php scripts/analyze-schema-jsonld.php
 *     php scripts/analyze-schema-jsonld.php [path-to.jsonld]
 *
 * The reports land in the add-on's own `src/Generation/Schema/`, which is
 * right only in its checkout. Installed in a host as a dependency, the add-on
 * has no `vendor/` of its own, so the script refuses to run there rather than
 * write into the host's `vendor/` directory, where the next `composer install`
 * would discard the reports.
 */
$autoload = dirname(__DIR__).'/vendor/autoload.php';

// Refuse outside a bootstrapped checkout of the add-on
if (! is_file($autoload)) {
    fwrite(STDERR, sprintf(
        'Error: %s not found. Run this script from a checkout of uams-web/aether-enums after `composer install`; installed as a dependency it would write into the host\'s vendor/ directory.%s',
        $autoload,
        PHP_EOL,
    ));

    exit(1);
}

require $autoload;

use UAMSWeb\Aether\Enums\Generation\Schema\SchemaJsonLdStructureAnalyzer;

exit(SchemaJsonLdStructureAnalyzer::runCli($_SERVER, SchemaJsonLdStructureAnalyzer::directory(), STDERR));
