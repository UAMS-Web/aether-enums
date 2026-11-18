<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;
use UAMSWeb\Aether\Enums\Generation\County\CountyEnumGenerator;

/**
 * Command to generate County_generated.php enum from TIGERweb.
 *
 * Thin wiring around {@see CountyEnumGenerator}: the generation logic lives in
 * the service so it can be tested in isolation, matching the other
 * `src/Generation/` generators.
 *
 * Usage:
 *   - php artisan generate:county-enum
 */
#[Description('Generate County_generated.php enum using TIGERweb API data')]
#[Signature('generate:county-enum')]
class GenerateCountyEnum extends Command
{
    /**
     * Execute the console command.
     *
     * @param  CountyEnumGenerator  $countyEnumGenerator  The TIGERweb county enum generator.
     */
    public function handle(CountyEnumGenerator $countyEnumGenerator): int
    {
        // Inform the user that we are starting a network fetch
        $this->info('Fetching TIGERweb county data...');

        try {
            // Delegate the fetch + merge + render + write to the generator service
            $outputPath = $countyEnumGenerator->generate();
        } catch (RuntimeException $runtimeException) {
            // Surface an actionable error and exit non-zero
            $this->error($runtimeException->getMessage());

            return 1;
        }

        // Report success and destination path
        $this->info('Wrote '.$outputPath);

        // Normal success exit status
        return 0;
    }
}
