<?php

declare(strict_types=1);

if (! function_exists('generatedEnumFiles')) {
    /**
     * The generated enum files, as paths relative to the add-on root with forward slashes.
     *
     * The list is the `<source><exclude>` list of `phpunit.xml`, the one place the other tools
     * (PHPStan, Rector, coverage) are kept in step with. The mutation scope reads it from here
     * rather than carrying a fourth copy.
     *
     * @return list<string>
     */
    function generatedEnumFiles(): array
    {
        $document = simplexml_load_file(dirname(__DIR__, 2).'/phpunit.xml');

        if ($document === false) {
            throw new RuntimeException('phpunit.xml could not be read.');
        }

        $files = [];

        foreach ($document->xpath('/phpunit/source/exclude/file') ?: [] as $file) {
            $files[] = ltrim((string) $file, './');
        }

        sort($files);

        return $files;
    }
}

if (! function_exists('mutationIgnoredPaths')) {
    /**
     * The paths the mutation run skips, in the form Pest's file finder matches.
     *
     * The finder compares each entry with the file's real path as a prefix, so an entry with
     * forward slashes never matches on Windows (measured: `src/Country.php` left the file in the
     * scope there). Each entry therefore uses the platform's directory separator.
     *
     * @return list<string>
     */
    function mutationIgnoredPaths(): array
    {
        return array_map(
            static fn (string $file): string => str_replace('/', DIRECTORY_SEPARATOR, $file),
            generatedEnumFiles(),
        );
    }
}
