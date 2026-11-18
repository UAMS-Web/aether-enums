<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums\Generation;

/**
 * Locates the files the enum generators read and write inside this add-on.
 *
 * The generators were written against a host application's `app/Enums`
 * directory. They now write into the add-on's own `src/` directory, which is
 * where the generated enums live, so a run from a checkout of the add-on
 * updates the enum files in place.
 */
final class EnumPaths
{
    /**
     * A directory that replaces `src/` as the place enum files are written, or null for `src/`.
     */
    private static ?string $enumDirectoryOverride = null;

    /**
     * A directory that replaces `src/Generation/` as the home of bundled fixtures, or null for `src/Generation/`.
     */
    private static ?string $generationDirectoryOverride = null;

    /**
     * Absolute path of the directory that holds the enum classes (`src/`).
     */
    public static function enumDirectory(): string
    {
        return self::$enumDirectoryOverride ?? \dirname(__DIR__);
    }

    /**
     * Write enum files to another directory, or back to `src/` with null (tests only).
     *
     * A test that runs a generator points it at a scratch directory so the
     * committed enums are never overwritten.
     *
     * @param  ?string  $directory  The directory to write to, or null to restore `src/`.
     */
    public static function useEnumDirectory(?string $directory): void
    {
        self::$enumDirectoryOverride = $directory;
    }

    /**
     * Absolute path of a directory under `src/Generation/`, where a generator keeps its bundled fixtures.
     *
     * @param  string  $name  The directory name, for example `Isco08`.
     */
    public static function generationDirectory(string $name): string
    {
        return (self::$generationDirectoryOverride ?? __DIR__).\DIRECTORY_SEPARATOR.$name;
    }

    /**
     * Read bundled fixtures from another directory, or back from `src/Generation/` with null (tests only).
     *
     * @param  ?string  $directory  The directory that holds the generator directories, or null to restore.
     */
    public static function useGenerationDirectory(?string $directory): void
    {
        self::$generationDirectoryOverride = $directory;
    }

    /**
     * Absolute path of an enum file in the enum directory.
     *
     * @param  string  $filename  The file name, for example `State.php`.
     * @return non-empty-string
     */
    public static function enumFile(string $filename): string
    {
        return self::enumDirectory().\DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Absolute path of the add-on's root directory, the one that holds `package.json`.
     */
    public static function root(): string
    {
        return \dirname(__DIR__, 2);
    }
}
