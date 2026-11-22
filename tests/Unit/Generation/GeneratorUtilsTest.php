<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\EnumPaths;
use UAMSWeb\Aether\Enums\Generation\GeneratorUtils;

/**
 * Unit tests for {@see GeneratorUtils::escapeSingleQuotes()}, the escaping the
 * state and International Code Designator generators use for single-quoted
 * literals (#717).
 *
 * @command  php artisan test --compact tests/Unit/Services/EnumGeneration/GeneratorUtilsTest.php
 */
it('escapes a value so the single-quoted literal evaluates back to it', function (string $value): void {
    $literal = "'".GeneratorUtils::escapeSingleQuotes($value)."'";

    // A literal that does not parse is a failure of this case, not of the run
    try {
        $evaluated = eval('return '.$literal.';');
    } catch (ParseError $parseError) {
        $evaluated = 'ParseError: '.$parseError->getMessage();
    }

    expect($evaluated)->toBe($value);
})->with([
    'empty' => [''],
    'plain' => ['Arkansas'],
    'apostrophe' => ["O'Hare"],
    'trailing backslash' => ['end\\'],
    'doubled backslash' => ['a\\\\b'],
    'backslash before apostrophe' => ['x\\\'y'],
    'lone backslash' => ['\\'],
    'escape-like sequence' => ['\\n\\t'],
]);

it('escapes backslashes before quotes, never double-escaping', function (): void {
    expect(GeneratorUtils::escapeSingleQuotes("a'b"))->toBe('a\\\'b')
        ->and(GeneratorUtils::escapeSingleQuotes('a\\b'))->toBe('a\\\\b')
        ->and(GeneratorUtils::escapeSingleQuotes('\\\''))->toBe('\\\\\\\'');
});

/**
 * `httpGet()` and `downloadToFile()` against a local server (UAMS-Web/aether-enums#28).
 */

/**
 * The request headers the local server's `/echo` path reports, decoded.
 *
 * @return array<string, ?string>
 */
function generatorUtilsEcho(?string $json): array
{
    $decoded = json_decode((string) $json, true);
    $echoed = [];

    foreach (is_array($decoded) ? $decoded : [] as $name => $value) {
        $echoed[(string) $name] = is_string($value) ? $value : null;
    }

    return $echoed;
}

/**
 * Fetch a path of the local server with `httpGet()`.
 *
 * @param  ?array{headers: array<int|string, string>, timeoutSeconds: int, userAgent: string}  $custom  Values to send in place of the defaults.
 */
function generatorUtilsGet(string $path, ?array $custom = null): ?string
{
    return withLocalHttpServer(fn (string $url): ?string => $custom === null
        ? GeneratorUtils::httpGet($url.$path)
        : GeneratorUtils::httpGet($url.$path, $custom['headers'], $custom['timeoutSeconds'], $custom['userAgent']));
}

/**
 * Run a callback with a scratch directory for a download, removed afterwards.
 *
 * @template T
 *
 * @param  Closure(string): T  $callback  Receives the directory.
 * @return T
 */
function generatorUtilsDownload(Closure $callback): mixed
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'download-'.md5(uniqid('', true));
    mkdir($dir);

    try {
        return $callback($dir);
    } finally {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($dir);
    }
}

/**
 * Download a path of the local server to a file and return the result and the file's content.
 *
 * @param  ?array{headers: array<int|string, string>, timeoutSeconds: int, userAgent: string}  $custom  Values to send in place of the defaults.
 * @return array{bool, string}
 */
function generatorUtilsFetch(string $path, ?array $custom = null): array
{
    return generatorUtilsDownload(fn (string $dir): array => withLocalHttpServer(function (string $url) use ($dir, $path, $custom): array {
        $destination = $dir.DIRECTORY_SEPARATOR.'out.txt';
        $ok = $custom === null
            ? GeneratorUtils::downloadToFile($url.$path, $destination)
            : GeneratorUtils::downloadToFile($url.$path, $destination, $custom['headers'], $custom['timeoutSeconds'], $custom['userAgent']);

        return [$ok, is_file($destination) ? (string) file_get_contents($destination) : ''];
    }));
}

it('returns the body of a 200 response', function (): void {
    expect(generatorUtilsEcho(generatorUtilsGet('/echo')))->not->toBe([]);
});

it('sends the default headers and user agent', function (): void {
    expect(generatorUtilsEcho(generatorUtilsGet('/echo')))->toBe([
        'user_agent' => 'UAMS-Statamic-Enum-Generator/1.0',
        'accept' => 'application/json',
        'probe' => null,
    ]);
});

it('sends the headers and user agent it is given, as pairs or as lines', function (): void {
    $echoed = generatorUtilsEcho(generatorUtilsGet('/echo', [
        'headers' => ['Accept' => 'text/plain', 'X-Probe: listed'],
        'timeoutSeconds' => 5,
        'userAgent' => 'Probe-Agent/2',
    ]));

    expect($echoed)->toBe(['user_agent' => 'Probe-Agent/2', 'accept' => 'text/plain', 'probe' => 'listed']);
});

it('follows a redirect', function (): void {
    expect(generatorUtilsEcho(generatorUtilsGet('/redirect')))->not->toBe([]);
});

it('returns null for a non-200 response', function (): void {
    expect(generatorUtilsGet('/missing'))->toBeNull();
});

it('returns null for a refused connection', function (): void {
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $name = is_resource($probe) ? (string) stream_socket_get_name($probe, false) : '';

    if (is_resource($probe)) {
        fclose($probe);
    }

    expect($name)->not->toBe('')
        ->and(GeneratorUtils::httpGet('http://'.$name.'/echo', timeoutSeconds: 2))->toBeNull();
});

it('gives up on a response slower than its timeout', function (): void {
    expect(generatorUtilsGet('/slow', ['headers' => ['Accept' => '*/*'], 'timeoutSeconds' => 1, 'userAgent' => 'Probe']))->toBeNull();
});

it('downloads a 200 response to the file, following a redirect, with the default headers', function (): void {
    [$ok, $content] = generatorUtilsFetch('/redirect');

    expect($ok)->toBeTrue()
        ->and(generatorUtilsEcho($content))->toBe([
            'user_agent' => 'UAMS-Statamic-Enum-Generator/1.0',
            'accept' => 'text/csv,application/csv,*/*',
            'probe' => null,
        ]);
});

it('downloads with the headers and user agent it is given', function (): void {
    [, $content] = generatorUtilsFetch('/echo', [
        'headers' => ['Accept' => 'text/plain', 'X-Probe: listed'],
        'timeoutSeconds' => 5,
        'userAgent' => 'Probe-Agent/2',
    ]);

    expect(generatorUtilsEcho($content))->toBe(['user_agent' => 'Probe-Agent/2', 'accept' => 'text/plain', 'probe' => 'listed']);
});

it('returns false for a non-200 download', function (): void {
    expect(generatorUtilsFetch('/missing')[0])->toBeFalse();
});

it('returns false for a download slower than its timeout', function (): void {
    expect(generatorUtilsFetch('/slow', ['headers' => ['Accept' => '*/*'], 'timeoutSeconds' => 1, 'userAgent' => 'Probe'])[0])->toBeFalse();
});

it('returns false when the destination cannot be opened', function (): void {
    set_error_handler(static fn (): bool => true);

    try {
        $result = GeneratorUtils::downloadToFile('http://127.0.0.1:1/never', sys_get_temp_dir().DIRECTORY_SEPARATOR.'no-such-dir-'.md5(uniqid('', true)).DIRECTORY_SEPARATOR.'out.txt');
    } finally {
        restore_error_handler();
    }

    expect($result)->toBeFalse();
});

it('builds a timestamped path in the enum directory for a base name and timezone', function (): void {
    $path = GeneratorUtils::makeTimestampedEnumPath('Probe', 'UTC');

    expect($path)->toStartWith(EnumPaths::enumFile('Probe_generated_'))
        ->and($path)->toMatch('/Probe_generated_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.php$/');
});

it('removes each byte order mark only at the start', function (string $bom): void {
    expect(GeneratorUtils::removeBom($bom.'value'))->toBe('value')
        ->and(GeneratorUtils::removeBom('value'.$bom))->toBe('value'.$bom);
})->with([
    'UTF-8' => "\xEF\xBB\xBF",
    'UTF-16 BE' => "\xFE\xFF",
    'UTF-16 LE' => "\xFF\xFE",
    'UTF-32 BE' => "\x00\x00\xFE\xFF",
    'UTF-32 LE' => "\xFF\xFE\x00\x00",
]);

it('turns a value into upper snake case without apostrophes or edge underscores', function (): void {
    expect(GeneratorUtils::toUpperSnake("Children's  Hospital-of (Arkansas)"))->toBe('CHILDRENS_HOSPITAL_OF_ARKANSAS')
        ->and(GeneratorUtils::toUpperSnake('__lower case__'))->toBe('LOWER_CASE')
        ->and(GeneratorUtils::toUpperSnake("'"))->toBe('');
});

it('names the add-on root as the directory that holds package.json', function (): void {
    expect(EnumPaths::root())->toBe(dirname(__DIR__, 3))
        ->and(EnumPaths::root().DIRECTORY_SEPARATOR.'package.json')->toBeFile();
});
