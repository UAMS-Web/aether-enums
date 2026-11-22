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

it('names nothing for an id that ends where the name would start', function (): void {
    $graph = [
        schemaGraphClass('Thing'),
        schemaGraphClass('DataType'),
        ['@id' => 'schema:', '@type' => 'rdfs:Class'],
        ['@id' => 'https://schema.org/', '@type' => ['rdfs:Class', 'schema:DataType']],
        ['@id' => 'schema:', '@type' => 'rdf:Property'],
        ['@id' => 'schema:', '@type' => 'schema:Color'],
        schemaGraphClass('Child', 'rdfs:subClassOf', ['schema:', 'schema:Thing']),
        schemaGraphProperty('name', 'schema:domainIncludes', ['schema:', 'schema:Thing'], 'schema:rangeIncludes', 'https://schema.org/'),
        schemaGraphClass('Color', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Enumeration'),
    ];

    $files = schemaGraphGenerate($graph)['files'];

    expect(array_keys(schemaGraphCases($files['SchemaType'])))->toBe(['Child', 'Color', 'Enumeration', 'Thing'])
        ->and(array_keys(schemaGraphCases($files['SchemaProperty'])))->toBe(['name'])
        ->and(array_keys(schemaGraphCases($files['SchemaDataType'])))->toBe(['DataType'])
        ->and(array_keys(schemaGraphCases($files['SchemaEnumerationMember'])))->toBe([])
        ->and(schemaGraphCases($files['SchemaType'])['Child']['attribute'])->toContain("rdfs_sub_class_of: ['Thing']")
        ->and(schemaGraphCases($files['SchemaProperty'])['name']['attribute'])->toContain("schema_domain_includes: ['Thing'], range_includes: []");
});

it('ignores a reference object that has no @id and reads a one-item list of references', function (): void {
    $graph = [
        schemaGraphClass('Thing'),
        schemaGraphClass('Alpha'),
        schemaGraphClass('Object', 'rdfs:subClassOf', ['x' => 'y'], 'schema:supersededBy', ['x' => 'y']),
        schemaGraphClass('Both', 'rdfs:subClassOf', ['@id' => 'schema:Thing', 0 => 'schema:Alpha']),
        schemaGraphClass('One', 'rdfs:subClassOf', ['schema:Thing'], 'schema:supersededBy', [['@id' => 'schema:Alpha']]),
        schemaGraphClass('Plain', 'rdfs:subClassOf', 'schema:Thing', 'schema:supersededBy', ['schema:Alpha']),
        schemaGraphClass('Holey', 'rdfs:subClassOf', [null, 'schema:Thing'], 'schema:supersededBy', [[], ['@id' => 'schema:Alpha']]),
        schemaGraphProperty('p', 'schema:domainIncludes', ['x' => 'y'], 'schema:rangeIncludes', ['x' => 'y'], 'schema:supersededBy', ['x' => 'y']),
    ];

    $files = schemaGraphGenerate($graph)['files'];
    $types = schemaGraphCases($files['SchemaType']);

    expect($types['Object']['attribute'])->toContain('rdfs_sub_class_of: [], children: [], ancestors: []')
        ->and($types['Object']['attribute'])->toContain('superseded_by: null, members: [])')
        ->and($types['Both']['attribute'])->toContain("rdfs_sub_class_of: ['Thing']")
        ->and($types['One']['attribute'])->toContain("rdfs_sub_class_of: ['Thing']")
        ->and($types['One']['attribute'])->toContain("superseded_by: 'Alpha'")
        ->and($types['Plain']['attribute'])->toContain('superseded_by: null')
        ->and($types['Holey']['attribute'])->toContain('rdfs_sub_class_of: []')
        ->and($types['Holey']['attribute'])->toContain('superseded_by: null')
        ->and(schemaGraphCases($files['SchemaProperty'])['p']['attribute'])->toBe('    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: [], superseded_by: null)]');
});

it('names nothing for an @id or @type entry that is an object without an @id', function (): void {
    $graph = [
        schemaGraphClass('Color', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Enumeration'),
        ['@id' => ['x' => 'y'], '@type' => 'rdfs:Class'],
        ['@id' => 'schema:Red', '@type' => [['x' => 'y'], 'schema:Color']],
        ['@id' => 'schema:Plain', '@type' => [['x' => 'y']]],
    ];

    $files = schemaGraphGenerate($graph)['files'];

    expect(array_keys(schemaGraphCases($files['SchemaType'])))->toBe(['Color', 'Enumeration'])
        ->and(array_keys(schemaGraphCases($files['SchemaEnumerationMember'])))->toBe(['Red']);
});

it('takes a node as a class, property or member only when its type list holds the exact type string', function (): void {
    $graph = [
        schemaGraphClass('Color', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Enumeration'),
        ['@id' => 'schema:NotAClass', '@type' => true],
        ['@id' => 'schema:notAProperty', '@type' => true],
        ['@id' => 'schema:Red', '@type' => [true, 'schema:Color']],
    ];

    $files = schemaGraphGenerate($graph)['files'];

    expect(array_keys(schemaGraphCases($files['SchemaType'])))->toBe(['Color', 'Enumeration'])
        ->and(array_keys(schemaGraphCases($files['SchemaProperty'])))->toBe([])
        ->and(array_keys(schemaGraphCases($files['SchemaEnumerationMember'])))->toBe(['Red']);
});

it('finds a data type added late in a chain that alternates between the two routes', function (): void {
    $graph = [
        ['@id' => 'schema:D', '@type' => 'schema:C'],
        ['@id' => 'schema:C', 'rdfs:subClassOf' => 'schema:B'],
        ['@id' => 'schema:B', '@type' => 'schema:A'],
        ['@id' => 'schema:A', 'rdfs:subClassOf' => 'schema:DataType'],
        ['@id' => 'schema:Other', '@type' => 'schema:Unrelated'],
    ];

    $dataTypes = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaDataType']);

    expect(array_keys($dataTypes))->toBe(['A', 'B', 'C', 'D', 'DataType']);
});

it('keeps a data type out of the type list and out of the children of its parent', function (): void {
    $warmer = new class implements AliasCacheWarmer
    {
        /** @var list<list<string>> */
        public array $calls = [];

        public function warm(array $typeNames): void
        {
            $this->calls[] = $typeNames;
        }
    };
    $graph = [
        schemaGraphClass('Gamma'),
        schemaGraphClass('Alpha'),
        schemaGraphClass('DataType'),
        ['@id' => 'schema:Beta', '@type' => ['rdfs:Class', 'schema:DataType'], 'rdfs:subClassOf' => 'schema:Alpha'],
    ];

    $generated = schemaGraphGenerate($graph, $warmer);
    $types = schemaGraphCases($generated['files']['SchemaType']);

    expect($warmer->calls)->toBe([['Alpha', 'Gamma']])
        ->and($types['Alpha']['attribute'])->toContain('children: [], ancestors: [], descendants: []');
});

it('reads a cycle and a diamond of parents once each', function (): void {
    $graph = [
        schemaGraphClass('A', 'rdfs:subClassOf', 'schema:B'),
        schemaGraphClass('B', 'rdfs:subClassOf', 'schema:A'),
        schemaGraphClass('Top'),
        schemaGraphClass('Left', 'rdfs:subClassOf', 'schema:Top'),
        schemaGraphClass('Right', 'rdfs:subClassOf', 'schema:Top'),
        schemaGraphClass('Bottom', 'rdfs:subClassOf', ['schema:Right', 'schema:Left']),
    ];

    $types = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaType']);

    expect($types['A']['attribute'])->toContain("rdfs_sub_class_of: ['B'], children: ['B'], ancestors: ['B'], descendants: ['B']")
        ->and($types['B']['attribute'])->toContain("rdfs_sub_class_of: ['A'], children: ['A'], ancestors: ['A'], descendants: ['A']")
        ->and($types['Bottom']['attribute'])->toContain("rdfs_sub_class_of: ['Right', 'Left'], children: [], ancestors: ['Left', 'Right', 'Top']")
        ->and($types['Top']['attribute'])->toContain("children: ['Left', 'Right'], ancestors: [], descendants: ['Bottom', 'Left', 'Right']");
});

it('merges the types of a repeated member in sorted order without repeats', function (): void {
    $graph = [
        schemaGraphClass('Enumeration'),
        schemaGraphClass('Zed', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Aye', 'rdfs:subClassOf', 'schema:Enumeration'),
        schemaGraphClass('Mid', 'rdfs:subClassOf', 'schema:Enumeration'),
        ['@id' => 'schema:Member', '@type' => ['schema:Zed', 'schema:Mid', 'schema:Zed']],
        ['@id' => 'schema:Member', '@type' => ['schema:Mid', 'schema:Aye']],
        ['@id' => 'schema:Member', '@type' => 'schema:Zed'],
        ['@id' => 'schema:Alone', '@type' => ['schema:Zed', 'schema:Aye', 'schema:Aye']],
        ['@id' => 'schema:Other', '@type' => ['schema:Enumeration', 'schema:NotEnumeration', 'plain']],
    ];

    $files = schemaGraphGenerate($graph)['files'];
    $members = schemaGraphCases($files['SchemaEnumerationMember']);
    $types = schemaGraphCases($files['SchemaType']);

    expect($members['Member']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Aye', 'Mid', 'Zed'], rdfs_comment: null)]")
        ->and($members['Alone']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Aye', 'Zed'], rdfs_comment: null)]")
        ->and($members['Other']['attribute'])->toBe("    #[SchemaEnumerationMemberInfo(type: ['Enumeration'], rdfs_comment: null)]")
        ->and($types['Zed']['attribute'])->toContain("members: ['Alone', 'Member'])")
        ->and($types['Aye']['attribute'])->toContain("members: ['Alone', 'Member'])");
});

it('lists a repeated class once and sorts the type names', function (): void {
    $graph = [
        schemaGraphClass('Mid'),
        schemaGraphClass('Zed'),
        schemaGraphClass('Aye'),
        schemaGraphClass('Mid', 'rdfs:comment', 'second'),
    ];

    $types = schemaGraphGenerate($graph)['files']['SchemaType'];

    expect(array_keys(schemaGraphCases($types)))->toBe(['Aye', 'Mid', 'Zed'])
        ->and(substr_count($types, "case Mid = 'Mid';"))->toBe(1);
});

it('lists a repeated domain or range once and in sorted order', function (): void {
    $graph = [
        schemaGraphClass('Aye'),
        schemaGraphClass('Bee'),
        schemaGraphProperty('p', 'schema:domainIncludes', ['schema:Bee', 'schema:Aye', 'schema:Bee'], 'schema:rangeIncludes', ['schema:Text', 'schema:Boolean', 'schema:Text']),
        schemaGraphProperty('p', 'schema:domainIncludes', ['schema:Aye', 'schema:Cee'], 'schema:rangeIncludes', 'schema:Boolean'),
        schemaGraphProperty('q', 'schema:rangeIncludes', ['schema:Time', 'schema:Boolean', 'schema:Time']),
    ];

    $properties = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaProperty']);

    expect($properties['p']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: ['Aye', 'Bee', 'Cee'], range_includes: ['Boolean', 'Role', 'Text', 'URL'], superseded_by: null)]")
        ->and($properties['q']['attribute'])->toBe("    #[SchemaPropertyInfo(rdfs_comment: null, schema_domain_includes: [], range_includes: ['Boolean', 'Time'], superseded_by: null)]");
});

/**
 * The sorted range list of a property whose ranges are the given names, with the OWL defaults added.
 *
 * @param  list<string>  $ranges
 */
function schemaGraphRangeList(array $ranges): string
{
    $all = array_values(array_unique(array_merge($ranges, ['Role', 'Text', 'URL'])));
    sort($all);

    return "range_includes: ['".implode("', '", $all)."'], superseded_by";
}

it('adds the OWL implied ranges unless every range is a datatype primitive', function (string $primitive): void {
    $graph = [
        schemaGraphProperty('only', 'schema:rangeIncludes', 'schema:'.$primitive),
        schemaGraphProperty('mixed', 'schema:rangeIncludes', ['schema:'.$primitive, 'schema:Thing']),
        schemaGraphProperty('role', 'schema:rangeIncludes', ['schema:'.$primitive, 'schema:Role', 'schema:URL']),
    ];

    $properties = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaProperty']);

    expect($properties['only']['attribute'])->toContain("range_includes: ['".$primitive."'], superseded_by")
        ->and($properties['mixed']['attribute'])->toContain(schemaGraphRangeList([$primitive, 'Thing']))
        ->and($properties['role']['attribute'])->toContain(schemaGraphRangeList([$primitive]));
})->with(['Boolean', 'Date', 'DateTime', 'Float', 'Integer', 'Number', 'Time']);

it('reads a comment of any shape and drops a blank one', function (): void {
    $graph = [
        schemaGraphClass('Blank', 'rdfs:comment', '   '),
        schemaGraphClass('BlankList', 'rdfs:comment', ['en', '   ']),
        schemaGraphClass('OneItem', 'rdfs:comment', ['only']),
        schemaGraphClass('Tagged', 'rdfs:comment', ['en', ' Spaced ']),
        schemaGraphClass('Keyed', 'rdfs:comment', ['a' => 5, 'b' => '  ', 'c' => ' text ']),
        schemaGraphClass('Numbers', 'rdfs:comment', [1, 2]),
        schemaGraphClass('Number', 'rdfs:comment', 5),
        schemaGraphClass('Trimmed', 'rdfs:comment', "\t padded \n"),
        schemaGraphClass('Skipped', 'rdfs:comment', [5, '  ', 'z']),
    ];

    $types = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaType']);
    $comment = static fn (string $name): string => explode(', rdfs_sub_class_of', $types[$name]['attribute'])[0];

    expect($comment('Blank'))->toBe('    #[SchemaTypeInfo(rdfs_comment: null')
        ->and($comment('BlankList'))->toBe('    #[SchemaTypeInfo(rdfs_comment: null')
        ->and($comment('OneItem'))->toBe("    #[SchemaTypeInfo(rdfs_comment: 'only'")
        ->and($comment('Tagged'))->toBe("    #[SchemaTypeInfo(rdfs_comment: 'Spaced'")
        ->and($comment('Keyed'))->toBe("    #[SchemaTypeInfo(rdfs_comment: 'text'")
        ->and($comment('Numbers'))->toBe('    #[SchemaTypeInfo(rdfs_comment: null')
        ->and($comment('Number'))->toBe('    #[SchemaTypeInfo(rdfs_comment: null')
        ->and($comment('Trimmed'))->toBe("    #[SchemaTypeInfo(rdfs_comment: 'padded'")
        ->and($comment('Skipped'))->toBe('    #[SchemaTypeInfo(rdfs_comment: null');
});

it('turns the markdown links of a description into anchors and trims what is inside them', function (): void {
    $graph = [
        schemaGraphClass('Links', 'rdfs:comment', 'A [](https://x.org/empty) B [t]( https://x.org/spaced ) C [[ Padded Name ]] D [s](/rel/path) E [bare](abs/path) F [u](HTTP://X.ORG/up)'),
    ];

    $attribute = schemaGraphCases(schemaGraphGenerate($graph)['files']['SchemaType'])['Links']['attribute'];
    $anchor = static fn (string $href, string $text): string => '<a href="'.$href.'" target="_blank" rel="noopener noreferrer">'.$text.'</a>';

    expect($attribute)->toContain("rdfs_comment: 'A ".$anchor('https://x.org/empty', '')
        .' B '.$anchor('https://x.org/spaced', 't')
        .' C '.$anchor('https://schema.org/Padded%20Name', 'Padded Name')
        .' D '.$anchor('https://schema.org/rel/path', 's')
        .' E '.$anchor('https://schema.org/abs/path', 'bare')
        .' F '.$anchor('HTTP://X.ORG/up', 'u')."'");
});

it('reads a data type, member or type description from whichever id form holds it and prefers the documented form', function (): void {
    $graph = [
        schemaGraphClass('DataType'),
        ['@id' => 'https://schema.org/UrlOnly', '@type' => 'schema:DataType', 'rdfs:comment' => 'url only'],
        ['@id' => 'schema:ShortOnly', '@type' => 'schema:DataType', 'rdfs:comment' => 'short only'],
        ['@id' => 'schema:Both', '@type' => 'schema:DataType', 'rdfs:comment' => 'both short'],
        ['@id' => 'https://schema.org/Both', '@type' => 'schema:DataType', 'rdfs:comment' => 'both url'],
        schemaGraphClass('Enumeration'),
        schemaGraphClass('Kind', 'rdfs:subClassOf', 'schema:Enumeration'),
        ['@id' => 'https://schema.org/MemberUrl', '@type' => 'schema:Kind', 'rdfs:comment' => 'member url only'],
        ['@id' => 'schema:MemberShort', '@type' => 'schema:Kind', 'rdfs:comment' => 'member short only'],
        ['@id' => 'schema:MemberBoth', '@type' => 'schema:Kind', 'rdfs:comment' => 'member both short'],
        ['@id' => 'https://schema.org/MemberBoth', '@type' => 'schema:Kind', 'rdfs:comment' => 'member both url'],
        ['@id' => 'schema:TypeBoth', '@type' => 'rdfs:Class', 'rdfs:comment' => 'type both short'],
        ['@id' => 'https://schema.org/TypeBoth', '@type' => 'rdfs:Class', 'rdfs:comment' => 'type both url'],
    ];

    $files = schemaGraphGenerate($graph)['files'];
    $dataTypes = schemaGraphCases($files['SchemaDataType']);
    $members = schemaGraphCases($files['SchemaEnumerationMember']);
    $types = schemaGraphCases($files['SchemaType']);

    expect($dataTypes['UrlOnly']['attribute'])->toBe("    #[SchemaDataTypeInfo(rdfs_comment: 'url only')]")
        ->and($dataTypes['ShortOnly']['attribute'])->toBe("    #[SchemaDataTypeInfo(rdfs_comment: 'short only')]")
        ->and($dataTypes['Both']['attribute'])->toBe("    #[SchemaDataTypeInfo(rdfs_comment: 'both url')]")
        ->and($members['MemberUrl']['attribute'])->toContain("rdfs_comment: 'member url only'")
        ->and($members['MemberShort']['attribute'])->toContain("rdfs_comment: 'member short only'")
        ->and($members['MemberBoth']['attribute'])->toContain("rdfs_comment: 'member both short'")
        ->and($types['TypeBoth']['attribute'])->toContain("rdfs_comment: 'type both url'");
});

it('slugs a type name by lower casing it and replacing spaces and hyphens with underscores', function (): void {
    $graph = [
        schemaGraphClass('Some-Hyphen Space Type'),
        schemaGraphClass('3DModel'),
    ];

    $php = schemaGraphGenerate($graph)['files']['SchemaType'];

    expect($php)->toContain("slug: 'some_hyphen_space_type'")
        ->and($php)->toContain("slug: '3dmodel'");
});

it('reads the schema.org document over HTTP, following a redirect, and gives null for a failed read', function (): void {
    $read = static function (string $url): ?string {
        $body = new ReflectionMethod(SchemaTypeEnumGenerator::class, 'readUrlOverHttp')->invoke(null, $url);

        return \is_string($body) ? $body : null;
    };

    withLocalHttpServer(function (string $base) use ($read): void {
        $direct = $read($base.'/echo');
        $redirected = $read($base.'/redirect');

        expect($direct)->toBeString()->toContain('"user_agent"')
            ->and($redirected)->toBe($direct)
            ->and($read($base.'/missing'))->toBeNull();
    });

    expect($read('http://127.0.0.1:1/nothing'))->toBeNull();
});
