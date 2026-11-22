<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\MimeType\MimeTypeEnumGenerator;

/**
 * The rules of MimeTypeEnumGenerator::parseExtensionsFromIanaPage() and parseExtensionText()
 * (src/Generation/MimeType/MimeTypeEnumGenerator.php) that the example rows of
 * GenerateMimeTypeEnumTest do not pin: which registration pattern a page needs, the order the
 * extensions come back in, and each filter a fragment passes through.
 *
 * Every row below fails with the one change it names, found by running the parser over generated
 * input with that change applied.
 */
it('keeps the extensions of each registration pattern, in the order the patterns are tried', function (string $content, array $expected): void {
    expect(array_values(new MimeTypeEnumGenerator()->parseExtensionsFromIanaPage($content)))->toBe($expected);
})->with([
    'a line that ends in a newline is read first' => ["exrFile extension: a or b\r\rFile extension: ,\"x\"\n\n", ['x', 'a', 'b']],
    'the rest of a line that ends in a carriage return' => ["File extension: a;b\r", ['a', 'b']],
    'the dotted run up to the end of the line, from a marker that shares its line' => ["File Extensions: x.y\r.a,b", ['x.y', 'b']],
    'the rest of the line up to its break, from a marker that shares its line' => ["File Extensions: \"x\"x.yFile Extensions: \r\"x\"", ['x']],
    'the leading word when the whole line is not an extension list' => ['File extension: a b', ['a']],
    'two markers on one line, the later one kept after the earlier' => ['File extension: File extension: a,b', ['file', 'b', 'a']],
    'a marker with only a form feed after it' => ["File extension(s): \f", []],
    'a marker with nothing after it' => ['File extension(s): ', []],
]);

it('returns no extension for a blank fragment', function (string $text): void {
    expect(new MimeTypeEnumGenerator()->parseExtensionText($text))->toBe([]);
})->with(['empty' => [''], 'a space' => [' '], 'a form feed' => ["\f"], 'a dash' => ['-'], 'a dash in spaces' => [' - '], 'not applicable' => ['None'], 'not applicable with a full stop' => ['not applicable.']]);

it('applies each filter of a fragment', function (string $text, array $expected): void {
    expect(new MimeTypeEnumGenerator()->parseExtensionText($text))->toBe($expected);
})->with([
    'a registered fragment with a parenthetical pair after a separator' => [';foo (bar) baz (qux)', []],
    'a quoted statement after a separator' => ['"a" is ;', []],
    'a dash part is skipped and the next part kept' => ['-;..a', ['a']],
    'a not applicable part is skipped and the next part kept' => ['N/A,A', ['a']],
    'a wildcard extension is lower cased' => ['*.A', ['a']],
    'a wildcard with nothing after the dot is dropped' => ['*.', []],
    'a wildcard part is skipped and the next part kept' => ['*.;b', ['b']],
    'a quoted wildcard keeps what follows the dot' => ["'*.v'", ['v']],
    'a part of dots is skipped and the next part kept' => ["..;'", ["'"]],
    'a part with a quote is skipped and the next part kept' => ['",b', ['b']],
    'a lone wildcard is skipped and the next part kept' => ['*,..a', ['a']],
    'a dotted not applicable is dropped' => ['.N/A', []],
    'a quoted not applicable is skipped and the next part kept, lower cased' => ['"n/A",X/Y', ['x/y']],
    'a numbered wildcard expands to the ten digits, lower cased' => ['AB<n>', ['ab0', 'ab1', 'ab2', 'ab3', 'ab4', 'ab5', 'ab6', 'ab7', 'ab8', 'ab9']],
    'a numbered wildcard does not end the parts after it' => ["AB<n> or '", ['ab0', 'ab1', 'ab2', 'ab3', 'ab4', 'ab5', 'ab6', 'ab7', 'ab8', 'ab9', "'"]],
    'a plain extension is lower cased' => ['A', ['a']],
    'a quoted dot leaves no extension' => ["'.'", []],
    'a trailing colon is dropped' => ['a:', ['a']],
    'a numbered quoted extension expands' => ['"a<n>"', ['a0', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9']],
    'a hash part is skipped and the next part kept' => ['x#y,z', ['z']],
    'a part with a space is skipped' => ['a b', []],
    'a dotted wildcard keeps its second dot' => ['*.a.b', ['a.b']],
    'a numbered wildcard with a base' => ['*.f<n>', ['f0', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6', 'f7', 'f8', 'f9']],
    'a wildcard of a format the generator once listed is an extension' => ['*.finf', ['finf']],
    'a dot and a null byte are not an extension' => [" .\0\f- - ;", []],
    'a null byte after a colon part is kept with the part before it' => [" and a:\0 - *.jpgm'y'", ['a:']],
    'a newline in a part drops it' => [" or  - \0\n*.x<n> ", []],
]);
