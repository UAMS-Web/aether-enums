<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use UAMSWeb\Aether\Enums\Generation\EnumPaths;
use UAMSWeb\Aether\Enums\Generation\Schema\AliasCacheWarmer;
use UAMSWeb\Aether\Enums\Generation\Schema\SchemaTypeEnumGenerator;

/**
 * SchemaTypeEnumGenerator (src/Generation/Schema/SchemaTypeEnumGenerator.php) over small hand-written
 * schema.org graphs, so each reading rule of the generator is pinned by an input that exercises it.
 * The committed-JSON-LD test in SchemaTypeEnumGeneratorTest covers the real graph; this file covers
 * the shapes that graph does not contain.
 */

/**
 * The words PHP will not accept as an enum case name, as the generator lists them.
 *
 * @var list<string>
 */
const SCHEMA_GRAPH_RESERVED_WORDS = [
    'class', 'default', 'case', 'enum', 'and', 'or', 'try', 'catch', 'finally', 'switch', 'match', 'return', 'extends',
    'implements', 'interface', 'trait', 'new', 'clone', 'instanceof', 'insteadof', 'as', 'goto', 'const', 'static',
    'abstract', 'final', 'private', 'protected', 'public', 'readonly', 'fn', 'function', 'if', 'else', 'elseif', 'endif',
    'for', 'endfor', 'foreach', 'endforeach', 'while', 'endwhile', 'do', 'break', 'continue', 'declare', 'enddeclare',
    'print', 'echo', 'require', 'require_once', 'include', 'include_once', 'list', 'unset', 'isset', 'empty', 'die',
    'exit', 'eval', 'yield', 'from', 'var', 'null', 'true', 'false',
];

/**
 * Run the generator over a graph and return the files it writes (keyed by enum name), its return value and the
 * directories it asked to have created.
 *
 * @param  list<array<string, mixed>>  $graph
 * @return array{files: array<string, string>, result: array<int, mixed>, dirs: list<string>, order: list<string>}
 */
function schemaGraphGenerate(array $graph, ?AliasCacheWarmer $warmer = null): array
{
    $json = json_encode(['@graph' => $graph], JSON_THROW_ON_ERROR);

    /** @var array<string, string> $written */
    $written = [];
    /** @var list<string> $dirs */
    $dirs = [];
    /** @var list<string> $order */
    $order = [];
    $mock = File::partialMock();
    $mock->shouldReceive('ensureDirectoryExists')->times(4)->andReturnUsing(static function (string $dir) use (&$dirs): bool {
        $dirs[] = $dir;

        return true;
    });
    $mock->shouldReceive('put')->times(4)->andReturnUsing(static function (string $path, string $php) use (&$written, &$order): int {
        $written[pathinfo($path, PATHINFO_FILENAME)] = $php;
        $order[] = pathinfo($path, PATHINFO_FILENAME);

        return \strlen($php);
    });

    $result = new SchemaTypeEnumGenerator(static fn (string $url): string => $json, $warmer)->generate();

    return ['files' => $written, 'result' => $result, 'dirs' => $dirs, 'order' => $order];
}

/**
 * Turn a flat list of key, value, key, value arguments into an array.
 *
 * @param  array<int|string, mixed>  $pairs
 * @return array<string, mixed>
 */
function schemaGraphPairs(array $pairs): array
{
    $out = [];
    foreach (array_chunk(array_values($pairs), 2) as [$key, $value]) {
        $out[mustBeString($key, 'a graph key')] = $value;
    }

    return $out;
}

/** @return array<string, mixed> */
function schemaGraphClass(string $name, mixed ...$extra): array
{
    return ['@id' => 'schema:'.$name, '@type' => 'rdfs:Class'] + schemaGraphPairs($extra);
}

/** @return array<string, mixed> */
function schemaGraphProperty(string $name, mixed ...$extra): array
{
    return ['@id' => 'schema:'.$name, '@type' => 'rdf:Property'] + schemaGraphPairs($extra);
}

/**
 * The attribute line and case line of each case in a generated enum, keyed by the case's string value.
 *
 * @return array<string, array{attribute: string, case: string}>
 */
function schemaGraphCases(string $php): array
{
    $cases = [];
    $lines = explode("\n", $php);
    foreach ($lines as $i => $line) {
        if (preg_match("/^    case (\\w+) = '([^']*)';\$/", $line, $m) === 1) {
            $cases[$m[2]] = ['attribute' => $lines[$i - 1], 'case' => $m[1]];
        }
    }

    return $cases;
}

it('maps every reserved word, in any letter case, to a prefixed case name in the three enums', function (string $word): void {
    $typeName = ucfirst($word);
    $graph = [
        schemaGraphClass('Thing'),
        schemaGraphClass($typeName),
        schemaGraphClass('DataType'),
        ['@id' => 'schema:'.$typeName.'Data', '@type' => ['rdfs:Class', 'schema:DataType']],
        schemaGraphProperty($word),
        schemaGraphProperty($typeName),
    ];

    $files = schemaGraphGenerate($graph)['files'];
    $types = schemaGraphCases($files['SchemaType']);
    $properties = schemaGraphCases($files['SchemaProperty']);

    expect($types[$typeName]['case'])->toBe('Type'.$typeName)
        ->and($types['Thing']['case'])->toBe('Thing')
        ->and($properties[$word]['case'])->toBe('Property'.$word)
        ->and($properties[$typeName]['case'])->toBe('Property'.$typeName);
})->with(SCHEMA_GRAPH_RESERVED_WORDS);

it('maps a reserved data type name to a prefixed case name', function (string $word): void {
    $name = ucfirst($word);
    $graph = [
        schemaGraphClass('DataType'),
        ['@id' => 'schema:'.$name, '@type' => ['rdfs:Class', 'schema:DataType']],
        ['@id' => 'schema:Plain', '@type' => ['rdfs:Class', 'schema:DataType']],
    ];

    $dataTypes = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaDataType']);

    expect($dataTypes[$name]['case'])->toBe('DataType'.$name)
        ->and($dataTypes['Plain']['case'])->toBe('Plain');
})->with(SCHEMA_GRAPH_RESERVED_WORDS);

it('prefixes a name that starts with a digit and keeps the rest of the name', function (): void {
    $graph = [
        schemaGraphClass('3DModel'),
        schemaGraphClass('DataType'),
        ['@id' => 'schema:3dData', '@type' => ['rdfs:Class', 'schema:DataType']],
        schemaGraphProperty('3dprop'),
        schemaGraphProperty('a1'),
    ];

    $files = schemaGraphGenerate($graph)['files'];

    expect(schemaGraphCases($files['SchemaType'])['3DModel']['case'])->toBe('Type3DModel')
        ->and(schemaGraphCases($files['SchemaDataType'])['3dData']['case'])->toBe('DataType3dData')
        ->and(schemaGraphCases($files['SchemaProperty'])['3dprop']['case'])->toBe('Property3dprop')
        ->and(schemaGraphCases($files['SchemaProperty'])['a1']['case'])->toBe('a1');
});

it('writes the four files in order, creating each directory, and returns their paths', function (): void {
    $out = schemaGraphGenerate([schemaGraphClass('Thing')]);

    expect($out['order'])->toBe(['SchemaProperty', 'SchemaDataType', 'SchemaEnumerationMember', 'SchemaType'])
        ->and($out['result'])->toBe([
            EnumPaths::enumFile('SchemaType.php'),
            EnumPaths::enumFile('SchemaProperty.php'),
            EnumPaths::enumFile('SchemaDataType.php'),
            false,
            EnumPaths::enumFile('SchemaEnumerationMember.php'),
        ])
        ->and($out['dirs'])->toBe([EnumPaths::enumDirectory(), EnumPaths::enumDirectory(), EnumPaths::enumDirectory(), EnumPaths::enumDirectory()]);
});

it('warms the alias cache with the type names, and carries on when the warmer throws', function (): void {
    $warmer = new class implements AliasCacheWarmer
    {
        /** @var list<list<string>> */
        public array $calls = [];

        public function warm(array $typeNames): void
        {
            $this->calls[] = $typeNames;
        }
    };
    $graph = [schemaGraphClass('Zed'), schemaGraphClass('Alpha')];

    $warmed = schemaGraphGenerate($graph, $warmer);

    expect($warmed['result'][3])->toBeTrue()
        ->and($warmer->calls)->toBe([['Alpha', 'Zed']]);

    $failing = new class implements AliasCacheWarmer
    {
        public function warm(array $typeNames): void
        {
            throw new RuntimeException('cache is down');
        }
    };

    $failed = schemaGraphGenerate($graph, $failing);

    expect($failed['result'][3])->toBeFalse()
        ->and($failed['files'])->toHaveCount(4);
});

it('names the URL when the download fails and rejects a body without a graph', function (): void {
    $fetchFails = static fn (): array => new SchemaTypeEnumGenerator(static fn (string $url): ?string => null)->generate();
    expect($fetchFails)->toThrow(RuntimeException::class, 'Failed to fetch schema.org JSON-LD from https://schema.org/version/latest/schemaorg-current-https.jsonld');

    foreach (['[]', '{"x":1}', '{"@graph":"text"}', '"text"', 'not json'] as $body) {
        $invalid = static fn (): array => new SchemaTypeEnumGenerator(static fn (string $url): string => $body)->generate();

        expect($invalid)->toThrow(RuntimeException::class, 'Invalid schema.org JSON-LD: missing @graph');
    }
});

/** @return list<array<string, mixed>> */
function schemaGraphWorld(): array
{
    return [
        schemaGraphClass('Thing'),
        schemaGraphClass('Alpha', 'rdfs:subClassOf', 'schema:Thing'),
        schemaGraphClass('Beta', 'rdfs:subClassOf', [['@id' => 'schema:Thing'], 'https://schema.org/Alpha', ['@id' => 'schema:Thing'], 'rdfs:Resource', 'schema:Missing', 7]),
        schemaGraphClass('Gamma', 'rdfs:subClassOf', ['@id' => 'schema:Beta'], 'rdfs:comment', ['skip', ' second '], 'schema:supersededBy', [['@id' => 'schema:Beta'], ['@id' => 'schema:Alpha']]),
        ['@id' => 'https://schema.org/Delta', '@type' => ['rdfs:Class'], 'rdfs:subClassOf' => 'schema:Gamma', 'rdfs:comment' => 'Delta [Alpha](/Alpha) & [ext](https://x.org/a?b=1&c="2") and [[Two Words]].', 'schema:supersededBy' => ['@id' => 'https://schema.org/Alpha']],
        ['@id' => 'http://example.org/Foreign', '@type' => 'rdfs:Class'],
        ['@type' => 'rdfs:Class'],
        schemaGraphClass('Orphan', 'rdfs:subClassOf', null, 'rdfs:comment', '   ', 'schema:supersededBy', 'schema:Alpha'),
        schemaGraphClass('Enumeration', 'rdfs:subClassOf', 'schema:Thing'),
        schemaGraphClass('Color', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Shade', 'rdfs:subClassOf', 'schema:Color'),
        ['@id' => 'schema:Red', '@type' => 'schema:Color'],
        ['@id' => 'schema:Blue', '@type' => ['schema:Color']],
        ['@id' => 'schema:Mixed', '@type' => ['schema:Shade', 'schema:Color', 'schema:Color', 'rdfs:Resource', 'plain']],
        ['@id' => 'schema:Red', '@type' => 'schema:Shade', 'rdfs:comment' => 'The [[Color]] red & "bold".'],
        ['@id' => 'schema:NoType'],
        ['@id' => 'schema:NotEnum', '@type' => 'schema:Alpha'],
        ['@id' => 'schema:AClass', '@type' => ['rdfs:Class', 'schema:Color']],
        ['@id' => 'http://example.org/Alien', '@type' => 'schema:Color'],
        schemaGraphClass('DataType'),
        ['@id' => 'schema:Integer', 'rdfs:subClassOf' => 'schema:Number'],
        ['@id' => 'schema:Number', '@type' => ['rdfs:Class'], 'rdfs:subClassOf' => ['@id' => 'schema:DataType']],
        ['@id' => 'schema:Text', '@type' => ['rdfs:Class', 'schema:DataType'], 'rdfs:comment' => 'Plain text.'],
        ['@id' => 'schema:Boolean', '@type' => 'schema:DataType'],
        ['@id' => 'schema:Date', '@type' => 'rdfs:Class', 'rdfs:subClassOf' => 'schema:Text'],
        schemaGraphProperty('name', 'schema:domainIncludes', 'schema:Thing', 'schema:rangeIncludes', 'schema:Text', 'rdfs:comment', ['  ', ' skipped ']),
        schemaGraphProperty('author', 'schema:domainIncludes', [['@id' => 'schema:Alpha'], 'https://schema.org/Beta', ['@id' => 'schema:Alpha'], 'other:Skipped'], 'schema:rangeIncludes', [['@id' => 'schema:Alpha'], 'schema:Alpha']),
        schemaGraphProperty('age', 'schema:rangeIncludes', ['schema:Integer', 'schema:Date']),
        schemaGraphProperty('blob', 'schema:domainIncludes', ['@id' => 'schema:Gamma'], 'schema:rangeIncludes', ['schema:Integer', 'schema:Text']),
        schemaGraphProperty('bare'),
        schemaGraphProperty('old', 'schema:domainIncludes', 'schema:Missing', 'schema:supersededBy', ['@id' => 'schema:name']),
        schemaGraphProperty('older', 'schema:supersededBy', [['@id' => 'schema:old'], ['@id' => 'schema:name']]),
        schemaGraphProperty('stringly', 'schema:supersededBy', 'schema:old'),
        ['@id' => 'http://example.org/foreignProp', '@type' => 'rdf:Property'],
        ['@type' => 'rdf:Property'],
        schemaGraphProperty('member', 'schema:domainIncludes', 'schema:Alpha', 'schema:rangeIncludes', 'schema:Thing'),
        schemaGraphClass('Role', 'rdfs:subClassOf', 'schema:Thing'),
        schemaGraphClass('OrganizationRole', 'rdfs:subClassOf', 'schema:Role'),
    ];
}

it('reads parents, children, ancestors and descendants from every reference shape', function (): void {
    $types = schemaGraphCases(schemaGraphGenerate(schemaGraphWorld())['files']['SchemaType']);

    expect(array_keys($types))->toBe(['AClass', 'Alpha', 'Beta', 'Color', 'Delta', 'Enumeration', 'Gamma', 'OrganizationRole', 'Orphan', 'Role', 'Shade', 'Thing'])
        ->and($types['Alpha']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: null, rdfs_sub_class_of: ['Thing'], children: ['Beta'], ancestors: ['Thing'], descendants: ['Beta', 'Delta', 'Gamma'], property_names: ['author', 'member', 'name'], slug: 'alpha', superseded_by: null, members: [])]")
        ->and($types['Beta']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: null, rdfs_sub_class_of: ['Thing', 'Alpha'], children: ['Gamma'], ancestors: ['Alpha', 'Thing'], descendants: ['Delta', 'Gamma'], property_names: ['author', 'member', 'name'], slug: 'beta', superseded_by: null, members: [])]")
        ->and($types['Orphan']['attribute'])->toContain("rdfs_sub_class_of: [], children: [], ancestors: [], descendants: [], property_names: [], slug: 'orphan'")
        ->and($types['AClass']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: null, rdfs_sub_class_of: [], children: [], ancestors: [], descendants: [], property_names: [], slug: 'aclass', superseded_by: null, members: [])]")
        ->and($types['Thing']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: null, rdfs_sub_class_of: [], children: ['Alpha', 'Beta', 'Enumeration', 'Role'], ancestors: [], descendants: ['Alpha', 'Beta', 'Color', 'Delta', 'Enumeration', 'Gamma', 'OrganizationRole', 'Role', 'Shade'], property_names: ['name'], slug: 'thing', superseded_by: null, members: [])]");
});

it('reads a type description from either id form and a type supersededBy from a reference list', function (): void {
    $types = schemaGraphCases(schemaGraphGenerate(schemaGraphWorld())['files']['SchemaType']);

    expect($types['Gamma']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: 'second', rdfs_sub_class_of: ['Beta'], children: ['Delta'], ancestors: ['Alpha', 'Beta', 'Thing'], descendants: ['Delta'], property_names: ['author', 'blob', 'member', 'name'], slug: 'gamma', superseded_by: 'Beta', members: [])]")
        ->and($types['Delta']['attribute'])->toBe("    #[SchemaTypeInfo(rdfs_comment: 'Delta <a href=\"https://schema.org/Alpha\" target=\"_blank\" rel=\"noopener noreferrer\">Alpha</a> & <a href=\"https://x.org/a?b=1&c=\"2\"\" target=\"_blank\" rel=\"noopener noreferrer\">ext</a> and <a href=\"https://schema.org/Two%20Words\" target=\"_blank\" rel=\"noopener noreferrer\">Two Words</a>.', rdfs_sub_class_of: ['Gamma'], children: [], ancestors: ['Alpha', 'Beta', 'Gamma', 'Thing'], descendants: [], property_names: ['author', 'blob', 'member', 'name'], slug: 'delta', superseded_by: 'Alpha', members: [])]")
        ->and($types['Orphan']['attribute'])->toContain('rdfs_comment: null')
        ->and($types['Orphan']['attribute'])->toContain('superseded_by: null');
});

it('lists the members of each enumeration type from every type shape and merges a repeated member', function (): void {
    $files = schemaGraphGenerate(schemaGraphWorld())['files'];
    $types = schemaGraphCases($files['SchemaType']);
    $members = schemaGraphCases($files['SchemaEnumerationMember']);

    expect(array_keys($members))->toBe(['Blue', 'Mixed', 'Red'])
        ->and($members['Blue']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Color'], rdfs_comment: null)]")
        ->and($members['Mixed']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Color', 'Shade'], rdfs_comment: null)]")
        ->and($members['Red']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Color', 'Shade'], rdfs_comment: 'The <a href=\"https://schema.org/Color\" target=\"_blank\" rel=\"noopener noreferrer\">Color</a> red & \"bold\".')]")
        ->and($types['Color']['attribute'])->toContain("members: ['Blue', 'Mixed', 'Red'])")
        ->and($types['Shade']['attribute'])->toContain("members: ['Mixed', 'Red'])")
        ->and($types['Alpha']['attribute'])->toContain('members: [])');
});

it('finds a data type through its type list or any depth of subclassing, in any node order', function (): void {
    $dataTypes = schemaGraphCases(schemaGraphGenerate(schemaGraphWorld())['files']['SchemaDataType']);

    expect(array_keys($dataTypes))->toBe(['Boolean', 'DataType', 'Date', 'Integer', 'Number', 'Text'])
        ->and($dataTypes['Text']['attribute'])->toBe("    #[SchemaDataTypeInfo(rdfs_comment: 'Plain text.')]")
        ->and($dataTypes['Integer']['attribute'])->toBe('    #[SchemaDataTypeInfo(rdfs_comment: null)]');
});

it('reads a property domain and range from each reference shape and adds the OWL implied ranges', function (): void {
    $properties = schemaGraphCases(schemaGraphGenerate(schemaGraphWorld())['files']['SchemaProperty']);

    expect(array_keys($properties))->toBe(['age', 'author', 'bare', 'blob', 'member', 'name', 'old', 'older', 'stringly'])
        ->and($properties['age']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: ['Date', 'Integer'], superseded_by: null)]")
        ->and($properties['author']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: ['Alpha', 'Beta', 'Role'], range_includes: ['Alpha', 'Role', 'Text', 'URL'], superseded_by: null)]")
        ->and($properties['bare']['attribute'])->toBe('    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: [], superseded_by: null)]')
        ->and($properties['blob']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: ['Gamma'], range_includes: ['Integer', 'Role', 'Text', 'URL'], superseded_by: null)]")
        ->and($properties['member']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: ['Alpha', 'Role'], range_includes: ['Role', 'Text', 'Thing', 'URL'], superseded_by: null)]")
        ->and($properties['name']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: 'skipped', schema_domain_includes: ['Thing'], range_includes: ['Role', 'Text', 'URL'], superseded_by: null)]")
        ->and($properties['old']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: ['Missing'], range_includes: [], superseded_by: 'name')]")
        ->and($properties['older']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: [], superseded_by: 'old')]")
        ->and($properties['stringly']['attribute'])->toBe('    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: [], superseded_by: null)]');
});

it('gives Role and its descendants every role wrappable property, and nothing else', function (): void {
    $types = schemaGraphCases(schemaGraphGenerate(schemaGraphWorld())['files']['SchemaType']);
    $names = ['affiliation', 'alumniOf', 'attendee', 'attendees', 'author', 'contributor', 'creator', 'creditedTo', 'founder', 'member', 'memberOf', 'members', 'name', 'organizer', 'participant', 'performer', 'performers', 'producer', 'publisher', 'translator', 'worksFor'];
    $expected = 'property_names: ['.implode(', ', array_map(static fn (string $n): string => var_export($n, true), $names)).'], slug:';

    expect($types['Role']['attribute'])->toContain($expected)
        ->and($types['OrganizationRole']['attribute'])->toContain($expected)
        ->and($types['Thing']['attribute'])->toContain("property_names: ['name'], slug:")
        ->and($types['Alpha']['attribute'])->not->toContain('affiliation');
});
