<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use UAMSWeb\Aether\Enums\Generation\EnumPaths;
use UAMSWeb\Aether\Enums\Generation\MimeType\MimeTypeEnumGenerator;

/**
 * `generate()` of {@see MimeTypeEnumGenerator} end to end (UAMS-Web/aether-enums#25): the
 * mime-db file, the IANA CSVs and detail pages and the Apache list are all answered by
 * fixtures, and the enum file it would write is captured instead of written.
 *
 * @command  vendor/bin/pest tests/Integration/Generation/MimeTypeEnumGeneratorGenerateTest.php
 */
beforeEach(function (): void {
    // The generator caches its downloads; keep that in memory, not in the shared file cache
    config(['cache.default' => 'array']);
});

const MIME_TYPE_APACHE_LIST = "# Before the header, so ignored\napplication/x-before\tbefore\n"
    ."# MIME type (lowercased) without the other column name\napplication/x-before-one\tone\n"
    ."# Extensions without the first column name\napplication/x-before-two\ttwo\n"
    ."   # MIME type (lowercased)\t\t\tExtensions\n"
    ."application/json\t\t\tjson jsn\n"
    ."application/x-apache-only\tapo apo2 APO\n"
    ."\n   \t\n"
    ."text/plain\t\t\ttxt text conf\n"
    ."Application/X-Upper\tUP\n"
    ."application/x-dup\tfirst\napplication/x-dup\tsecond\n"
    ."not-a-pair\n"
    ."bad type\text\n"
    ."image/x-nothing\n";

const MIME_TYPE_IANA_APPLICATION_CSV = "Name,Template,Reference\n"
    ."json,application/json,[RFC8259]\n"
    ."X-IanaOnly,application/x-ianaonly,\n"
    ."\"quoted, name\",x,\n"
    .",template,\n"
    ." ,x,\n"
    ."  X-Spaced ,x,\n"
    ."\n   \n"
    ."X-Dup,application/x-dup,\n"
    ."ZIP,application/zip,\n";

/**
 * The mime-db fixture: mixed case keys, a type that is not valid, dots and duplicates in the extensions.
 */
function mimeTypeMimeDbJson(): string
{
    return (string) json_encode([
        'Application/JSON' => ['extensions' => ['json', '.JSON', '', '.map']],
        'text/Plain' => ['compressible' => true],
        'video/mp4' => ['extensions' => ['mp4', '.M4V', '.MP4']],
        'application/zip' => ['extensions' => ['zip', '.ZIP']],
        'bad type' => ['extensions' => ['x']],
        'application/x-a.b' => [],
        'application/x-a-b' => ['extensions' => ['ab']],
    ]);
}

/**
 * Answer the downloads of a generator run, and record each request.
 *
 * @param  list<array{url: string, headers: array<string, string>, timeout: int}>  $requests
 */
function mimeTypeAnswers(array &$requests): Closure
{
    return static function (string $url, array $headers, int $timeout) use (&$requests): ?string {
        $requests[] = ['url' => $url, 'headers' => $headers, 'timeout' => $timeout];

        if ($url === 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types') {
            return MIME_TYPE_APACHE_LIST;
        }

        if ($url === 'https://www.iana.org/assignments/media-types/application.csv') {
            return MIME_TYPE_IANA_APPLICATION_CSV;
        }

        if (preg_match('#/media-types/([a-z]+)\.csv$#', $url, $matches) === 1) {
            return "Name,Template,Reference\nx-probe,{$matches[1]}/x-probe,\n";
        }

        return match ($url) {
            'https://www.iana.org/assignments/media-types/application/x-ianaonly' => 'File extension(s): zzz',
            'https://www.iana.org/assignments/media-types/application/x-dup' => 'File extension(s): zz',
            'https://www.iana.org/assignments/media-types/video/mp4' => 'File extension(s): mp4',
            default => null,
        };
    };
}

/**
 * Run `generate()` with the given downloads, capturing the write.
 *
 * @param  ?string  $mimeDb  The mime-db file content, or null for no file
 * @return array{path: string, php: string, returned: string}
 */
function mimeTypeGenerate(Closure $httpGet, ?string $mimeDb): array
{
    $captured = (object) ['path' => '', 'php' => ''];
    File::partialMock()->shouldReceive('put')->once()->andReturnUsing(static function (string $path, string $php) use ($captured): int {
        $captured->path = $path;
        $captured->php = $php;

        return \strlen($php);
    });

    $mimeDbPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mime-db-'.md5(uniqid('', true)).'.json';
    if ($mimeDb !== null) {
        file_put_contents($mimeDbPath, $mimeDb);
    }

    try {
        $returned = new MimeTypeEnumGenerator($httpGet, $mimeDbPath)->generate();
    } finally {
        if (file_exists($mimeDbPath)) {
            unlink($mimeDbPath);
        }
    }

    return ['path' => $captured->path, 'php' => $captured->php, 'returned' => $returned];
}

/**
 * The case lines of a generated enum for the given rows.
 *
 * @param  list<array{string, list<string>, string, string}>  $rows  MIME type, extensions, category, case id
 */
function mimeTypeCaseLines(array $rows): string
{
    $lines = '';
    foreach ($rows as [$mimeType, $extensions, $category, $caseId]) {
        $list = implode(', ', array_map(static fn (string $extension): string => "'{$extension}'", $extensions));
        $lines .= "    #[MimeTypeInfo(mimeType: '{$mimeType}', extensions: [{$list}], category: '{$category}')]\n"
            ."    case {$caseId} = '{$caseId}';\n\n";
    }

    return $lines;
}

it('merges the three sources into one sorted enum, with the mime-db record winning', function (): void {
    $requests = [];
    cache()->put('iana_csv_text', "Name,Template,Reference\nplain,text/plain,\nx-cached,text/x-cached,\n");
    cache()->put('iana_mime_detail_text/plain', 'File extension(s): asc');

    $run = mimeTypeGenerate(mimeTypeAnswers($requests), mimeTypeMimeDbJson());

    $header = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * Generated MIME Types enum cases\n *\n * INSTRUCTIONS:\n"
        ." * 1. Copy all the case statements below (from the first case to the\n *    closing brace)\n"
        ." * 2. Open src/MimeType.php\n * 3. Replace the existing case list in the MimeType enum with the\n *    copied cases\n"
        ." * 4. Keep any other methods in MimeType.php; the MimeTypeInfo attribute\n *    class is in MimeTypeInfo.php\n"
        ." * 5. Save the file\n *\n * Data Source:\n"
        ." *   - mime-db database:\n *     https://github.com/jshttp/mime-db\n"
        ." *   - IANA Media Types Registry:\n *     https://www.iana.org/assignments/media-types/media-types.xhtml\n"
        ." *   - Apache HTTP Server MIME Types:\n *     https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types\n"
        ." *\n * This list of cases was generated on DATE at TIME\n * Total MIME types: 19\n */\nenum MimeType: string\n{\n";
    $php = preg_replace('/generated on \S+ at \S+( \S+)?\n/', "generated on DATE at TIME\n", $run['php']);

    expect($run['returned'])->toBe($run['path'])
        ->and(str_replace('\\', '/', $run['path']))->toMatch('#/src/MimeType_generated_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.php$#')
        ->and($php)->toBe($header.mimeTypeCaseLines([
            ['application/json', ['json', 'map', 'jsn'], 'application', 'APPLICATION_JSON'],
            ['application/x-a.b', [], 'application', 'APPLICATION_X_A_B'],
            ['application/x-apache-only', ['apo', 'apo2'], 'application', 'APPLICATION_X_APACHE_ONLY'],
            ['application/x-dup', ['first', 'zz', 'second'], 'application', 'APPLICATION_X_DUP'],
            ['application/x-ianaonly', ['zzz'], 'application', 'APPLICATION_X_IANAONLY'],
            ['application/x-spaced', [], 'application', 'APPLICATION_X_SPACED'],
            ['application/x-upper', ['up'], 'application', 'APPLICATION_X_UPPER'],
            ['application/zip', ['zip'], 'application', 'APPLICATION_ZIP'],
            ['audio/x-probe', [], 'audio', 'AUDIO_X_PROBE'],
            ['font/x-probe', [], 'font', 'FONT_X_PROBE'],
            ['haptics/x-probe', [], 'haptics', 'HAPTICS_X_PROBE'],
            ['image/x-probe', [], 'image', 'IMAGE_X_PROBE'],
            ['message/x-probe', [], 'message', 'MESSAGE_X_PROBE'],
            ['model/x-probe', [], 'model', 'MODEL_X_PROBE'],
            ['multipart/x-probe', [], 'multipart', 'MULTIPART_X_PROBE'],
            ['text/plain', ['asc', 'txt', 'text', 'conf'], 'text', 'TEXT_PLAIN'],
            ['text/x-cached', [], 'text', 'TEXT_X_CACHED'],
            ['video/mp4', ['mp4', 'm4v'], 'video', 'VIDEO_MP4'],
            ['video/x-probe', [], 'video', 'VIDEO_X_PROBE'],
        ])."}\n");
});

it('requests each source once, with its headers and timeout, and skips what the cache holds', function (): void {
    $requests = [];
    cache()->put('iana_csv_text', "Name,Template,Reference\nplain,text/plain,\nx-cached,text/x-cached,\n");
    cache()->put('iana_mime_detail_text/plain', 'File extension(s): asc');

    mimeTypeGenerate(mimeTypeAnswers($requests), mimeTypeMimeDbJson());

    $base = 'https://www.iana.org/assignments/media-types/';
    $csv = array_map(static fn (string $category): string => $base.$category.'.csv', ['application', 'audio', 'font', 'haptics', 'image', 'message', 'model', 'multipart', 'video']);
    $details = array_map(static fn (string $mimeType): string => $base.$mimeType, [
        'application/json', 'application/x-apache-only', 'application/x-upper', 'application/x-dup', 'application/x-ianaonly',
        'application/x-spaced', 'application/zip',
        'audio/x-probe', 'font/x-probe', 'haptics/x-probe', 'image/x-probe', 'message/x-probe', 'model/x-probe',
        'multipart/x-probe', 'text/x-cached', 'video/x-probe', 'video/mp4', 'application/x-a.b', 'application/x-a-b',
    ]);

    expect(array_column($requests, 'url'))->toBe([...$csv, 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types', ...$details]);

    $byUrl = array_column($requests, null, 'url');
    expect($byUrl['https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'])->toMatchArray(['headers' => ['Accept' => 'text/plain'], 'timeout' => 60])
        ->and($byUrl[$csv[0]])->toMatchArray(['headers' => ['Accept' => 'text/csv'], 'timeout' => 60])
        ->and($byUrl[$details[0]])->toMatchArray(['headers' => ['Accept' => 'text/html'], 'timeout' => 30]);
});

it('keeps the Apache list for 24 hours and each IANA download for 30 days', function (): void {
    $requests = [];
    $this->freezeSecond();
    $fetchedAt = now();

    mimeTypeGenerate(mimeTypeAnswers($requests), mimeTypeMimeDbJson());

    $keys = ['apache_mime_types' => 24, 'iana_csv_application' => 30 * 24, 'iana_csv_video' => 30 * 24, 'iana_mime_detail_application/x-ianaonly' => 30 * 24];
    foreach ($keys as $key => $hours) {
        expect(cache()->has($key))->toBeTrue("{$key} is cached");
        $this->travelTo($fetchedAt->copy()->addHours($hours)->subSecond());
        expect(cache()->has($key))->toBeTrue("{$key} is kept until {$hours} hours");
        $this->travelTo($fetchedAt->copy()->addHours($hours));
        expect(cache()->has($key))->toBeFalse("{$key} expires at {$hours} hours");
        $this->travelTo($fetchedAt);
    }

    expect(cache()->has('iana_mime_detail_application/json'))->toBeFalse('a failed download is not cached');
});

it('uses a cached Apache list without downloading it', function (): void {
    $requests = [];
    cache()->put('apache_mime_types', "# MIME type (lowercased)\t\t\tExtensions\napplication/x-from-cache\tfc\n");

    $run = mimeTypeGenerate(mimeTypeAnswers($requests), null);

    expect(array_column($requests, 'url'))->not->toContain('https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types')
        ->and($run['php'])->toContain("mimeType: 'application/x-from-cache', extensions: ['fc'], category: 'application'");
});

it('writes an empty enum when no source answers', function (): void {
    $run = mimeTypeGenerate(static fn (string $url, array $headers, int $timeout): ?string => null, null);

    expect($run['php'])->toContain(" * Total MIME types: 0\n */\nenum MimeType: string\n{\n}\n");
});

it('treats a mime-db file that is not a JSON object as missing', function (string $content): void {
    $run = mimeTypeGenerate(static fn (string $url, array $headers, int $timeout): ?string => str_ends_with($url, 'mime.types')
        ? "# MIME type (lowercased)\t\t\tExtensions\ntext/x-only\tonly\n"
        : null, $content);

    expect($run['php'])->toContain(" * Total MIME types: 1\n")
        ->and($run['php'])->toContain("mimeType: 'text/x-only'");
})->with([
    'text that is not JSON' => ['not json'],
    'a number' => ['5'],
    'a string' => ['"text"'],
]);

it('treats a mime-db path that cannot be read as missing', function (): void {
    $captured = (object) ['php' => ''];
    File::partialMock()->shouldReceive('put')->once()->andReturnUsing(static function (string $path, string $php) use ($captured): int {
        $captured->php = $php;

        return \strlen($php);
    });
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mime-db-dir-'.md5(uniqid('', true));
    mkdir($directory);
    set_error_handler(static fn (): bool => true);

    try {
        new MimeTypeEnumGenerator(static fn (string $url, array $headers, int $timeout): ?string => null, $directory)->generate();
    } finally {
        restore_error_handler();
        rmdir($directory);
    }

    expect($captured->php)->toContain(" * Total MIME types: 0\n");
});

it('adds the extensions an IANA detail page lists and leaves a page without any alone', function (): void {
    $run = mimeTypeGenerate(static fn (string $url, array $headers, int $timeout): ?string => match ($url) {
        'https://www.iana.org/assignments/media-types/application.csv' => "Name\nx-one\nx-two\n",
        'https://www.iana.org/assignments/media-types/application/x-one' => 'File extension(s): one, uno',
        'https://www.iana.org/assignments/media-types/application/x-two' => 'No extension is listed here.',
        default => null,
    }, null);

    expect($run['php'])->toContain("mimeType: 'application/x-one', extensions: ['one', 'uno'], category: 'application'")
        ->and($run['php'])->toContain("mimeType: 'application/x-two', extensions: [], category: 'application'");
});

it('reads mime-db from node_modules under the repository root unless told otherwise', function (): void {
    $property = new ReflectionProperty(MimeTypeEnumGenerator::class, 'mimeDbPath');

    expect($property->getValue(new MimeTypeEnumGenerator))->toBe(EnumPaths::root().'/node_modules/mime-db/db.json')
        ->and($property->getValue(new MimeTypeEnumGenerator(null, '/elsewhere/db.json')))->toBe('/elsewhere/db.json');
});

it('lists the given data source lines trimmed on the right, or a placeholder when there are none', function (): void {
    $row = ['mimeType' => 'text/plain', 'extensions' => ['txt'], 'category' => 'text', 'caseId' => 'TEXT_PLAIN'];
    $generator = new MimeTypeEnumGenerator;

    $withSources = $generator->generatePhpContent([$row], ["  - first source  \t", '  - second source']);
    $without = $generator->generatePhpContent([$row]);

    expect($withSources)->toContain(" * Data Source:\n *   - first source\n *   - second source\n *\n * This list of cases was generated on ")
        ->and($without)->toContain(" * Data Source:\n * - Apache MIME Types\n *\n * This list of cases was generated on ")
        ->and($without)->toStartWith("<?php\n\ndeclare(strict_types=1);\n\n/**\n * Generated MIME Types enum cases\n")
        ->and($without)->toContain(" * Total MIME types: 1\n */\nenum MimeType: string\n{\n    #[MimeTypeInfo(");
});

it('takes the category from the part before the slash, empty when there is none', function (): void {
    $run = mimeTypeGenerate(static fn (string $url, array $headers, int $timeout): ?string => null, (string) json_encode([
        'text/x-a' => [],
        '/x-no-type' => [],
    ]));

    expect($run['php'])->toContain("mimeType: 'text/x-a', extensions: [], category: 'text')]")
        ->and($run['php'])->toContain("mimeType: '/x-no-type', extensions: [], category: '')]");
});

it('carries on with the next IANA category when one download fails', function (): void {
    $requests = [];
    $answers = mimeTypeAnswers($requests);

    $run = mimeTypeGenerate(static fn (string $url, array $headers, int $timeout): ?string => str_ends_with($url, '/font.csv') ? null : $answers($url, $headers, $timeout), null);

    expect($run['php'])->not->toContain("mimeType: 'font/x-probe'")
        ->and($run['php'])->toContain("mimeType: 'audio/x-probe'")
        ->and($run['php'])->toContain("mimeType: 'image/x-probe'")
        ->and($run['php'])->toContain("mimeType: 'video/x-probe'");
});

it('trims the underscores a MIME type leaves at either end of its case id', function (string $mimeType, string $expected): void {
    expect((new MimeTypeEnumGenerator)->generateCaseId($mimeType))->toBe($expected);
})->with([
    'leading plus' => ['+json', 'JSON'],
    'trailing slash' => ['x/', 'X'],
    'both ends' => ['/x-y.z/', 'X_Y_Z'],
]);
