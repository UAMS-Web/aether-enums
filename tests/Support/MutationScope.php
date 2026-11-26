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

if (! function_exists('coversTargets')) {
    /**
     * The classes a test file's top-level `covers()` calls name, as fully qualified names.
     *
     * Each `Name::class` argument is resolved against the file's `use` imports, so
     * `use Vendor\County; covers(County::class);` yields `Vendor\County`. A name with no import is
     * returned as written. A `covers()` that names a class outside the coverage source (such as
     * a generated enum on the `phpunit.xml` exclude list) makes PHPUnit warn when coverage is
     * collected, and `failOnWarning` turns that warning into a failed run (#97).
     *
     * @return list<string>
     */
    function coversTargets(string $code): array
    {
        $names = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];
        $tokens = array_values(array_filter(
            token_get_all($code),
            static fn (array|string $token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));
        $count = count($tokens);

        // Read the `use` imports: alias (last segment, or the `as` name) => fully qualified name.
        $imports = [];

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i]) || $tokens[$i][0] !== T_USE) {
                continue;
            }

            $next = $tokens[$i + 1] ?? null;

            if (! is_array($next) || ! in_array($next[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING], true)) {
                continue;
            }

            $name = ltrim($next[1], '\\');
            $alias = substr($name, (int) strrpos('\\'.$name, '\\'));
            $after = $tokens[$i + 2] ?? null;

            if (is_array($after) && $after[0] === T_AS && is_array($tokens[$i + 3] ?? null)) {
                $alias = $tokens[$i + 3][1];
            }

            $imports[$alias] = $name;
        }

        // Read each `covers(...)` call's `Name::class` arguments.
        $targets = [];

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            $previous = $tokens[$i - 1] ?? null;

            if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'covers' || ($tokens[$i + 1] ?? null) !== '(') {
                continue;
            }

            if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                continue;
            }

            for ($j = $i + 2, $depth = 1; $j < $count && $depth > 0; $j++) {
                $depth += match ($tokens[$j]) {
                    '(' => 1,
                    ')' => -1,
                    default => 0,
                };

                $name = $tokens[$j];

                if (! is_array($name) || ! in_array($name[0], $names, true)) {
                    continue;
                }

                $class = $tokens[$j + 2] ?? null;

                if (! is_array($tokens[$j + 1] ?? null) || $tokens[$j + 1][0] !== T_DOUBLE_COLON || ! is_array($class) || $class[0] !== T_CLASS) {
                    continue;
                }

                if ($name[0] === T_NAME_FULLY_QUALIFIED) {
                    $targets[] = ltrim($name[1], '\\');

                    continue;
                }

                $segments = explode('\\', $name[1], 2);
                $head = $imports[$segments[0]] ?? $segments[0];
                $targets[] = isset($segments[1]) ? $head.'\\'.$segments[1] : $head;
            }
        }

        return $targets;
    }
}
