<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\EnumPaths;

if (! function_exists('useScratchEnumPaths')) {
    /**
     * Point the generators at a scratch directory, and fail before any generator runs if the
     * redirect did not take.
     *
     * A test that runs a generator relies on `EnumPaths` to send its output to the scratch
     * directory, so a mutant of `EnumPaths` itself (the override ignored, or the directory
     * dropped from a path) sends the same writes into the committed `src/` or the working
     * directory: a mutation run overwrote five tracked enums that way (UAMS-Web/aether-enums#23).
     * The probe below checks the redirect first, so such a mutant fails the test before the
     * generator writes anything.
     *
     * @param  string  $enumDirectory  The directory enum files are written to.
     * @param  ?string  $generationDirectory  The directory that holds the generators' bundled fixtures, when the test redirects it too.
     */
    function useScratchEnumPaths(string $enumDirectory, ?string $generationDirectory = null): void
    {
        EnumPaths::useEnumDirectory($enumDirectory);

        if ($generationDirectory !== null) {
            EnumPaths::useGenerationDirectory($generationDirectory);
        }

        $problems = [];

        if (EnumPaths::enumDirectory() !== $enumDirectory) {
            $problems[] = 'enumDirectory() is '.EnumPaths::enumDirectory();
        }

        if (EnumPaths::enumFile('probe.php') !== $enumDirectory.DIRECTORY_SEPARATOR.'probe.php') {
            $problems[] = "enumFile('probe.php') is ".EnumPaths::enumFile('probe.php');
        }

        if ($generationDirectory !== null && EnumPaths::generationDirectory('probe') !== $generationDirectory.DIRECTORY_SEPARATOR.'probe') {
            $problems[] = "generationDirectory('probe') is ".EnumPaths::generationDirectory('probe');
        }

        if ($problems !== []) {
            throw new RuntimeException('The generators are not redirected to the scratch directory '.$enumDirectory.': '.implode('; ', $problems).'.');
        }
    }
}
