Written against v0.2.1.

Four enums list the schema.org vocabulary, all in the `UAMSWeb\Aether\Enums` namespace. `generate:schema-type-enum` writes all four from the schema.org JSON-LD in one run.

| Enum | Cases | Backing value (what `from()` takes) | Example |
| --- | --- | --- | --- |
| `SchemaType` | 915 | The schema.org type name | `SchemaType::from('Person')` |
| `SchemaProperty` | 1,521 | The property name | `SchemaProperty::from('about')` |
| `SchemaDataType` | 20 | The data type name | `SchemaDataType::from('Boolean')` |
| `SchemaEnumerationMember` | 531 | The member name | `SchemaEnumerationMember::from('AlbumRelease')` |

A name PHP cannot use as a case name gets a prefix: the type `3DModel` is the case `SchemaType::Type3DModel`, and its backing value is still `3DModel`.

## SchemaType

Instance methods:

| Method | Returns | Example for `SchemaType::Person` |
| --- | --- | --- |
| `label()` | `string` | `Person` |
| `toSchemaOrgUrl()` | `string` | `https://schema.org/Person` |
| `typeDescription()` | `?string`, the schema.org description, as HTML | |
| `supersededBy()` | `?string`, the name of the type or property that replaces this one | `null` |
| `properties()` | `array<string, string>`, each property name with its description | |
| `propertyNames()` | `array<int, string>` | 81 names |
| `hasProperty(string $name)` | `bool` | `hasProperty('name')` is `true` |
| `propertyDescription(string $name)` | `?string` | `The name of the item.` for `name` |
| `members()` | `array<int, string>`, the member names of an enumeration type | `[]` for a type that is not an enumeration |
| `info()` | `?SchemaTypeInfo`, the case's attribute | |

Static methods, which take a type name as a string:

| Method | Returns |
| --- | --- |
| `parentsOf($type)` | The direct parents (`["Thing"]` for `Person`); `[]` for a name the enum does not have |
| `childrenOf($type)` | The direct children |
| `ancestorsOf($type)` | Every ancestor (`["LocalBusiness", "MedicalBusiness", "MedicalOrganization", "Organization", "Place", "Thing"]` for `Physician`) |
| `descendantsOf($type)` | Every descendant |
| `propertyNamesFor($type)` | The property names of the type |
| `typeDescriptionFor(?string $type)` | `?string`, `null` for an unknown or `null` type |
| `propertyDescriptionFor(?string $type, ?string $property)` | `?string` |
| `supersededByFor($type)` | `?string` |
| `membersFor($type)` | The member names of an enumeration type |

## SchemaProperty

| Method | Returns | Example for `SchemaProperty::about` |
| --- | --- | --- |
| `label()` | `string` | `about` |
| `description()` | `?string`, the schema.org description | |
| `domainTypes()` | `array<int, string>`, the types the property applies to | `["Certification", "CommunicateAction", "CreativeWork", "DefinedTerm", "DefinedTermSet", "Event"]` |
| `rangeTypes()` | `array<int, string>`, the types of value it takes | `["Role", "Text", "Thing", "URL"]` |
| `supersededBy()` | `?string` | `null` |
| `info()` | `?SchemaPropertyInfo` | |

## SchemaDataType

| Method | Returns | Example for `SchemaDataType::Distance` |
| --- | --- | --- |
| `label()` | `string` | `Distance` |
| `description()` | `?string` | `Properties that take Distances as values are of the form '<Number> <Length unit of measure>'. E.g., '7 ft'.` |
| `info()` | `?SchemaDataTypeInfo` | |

## SchemaEnumerationMember

| Method | Returns | Example for `SchemaEnumerationMember::AlbumRelease` |
| --- | --- | --- |
| `type()` | `array<int, string>`, the enumeration types the member belongs to | `["MusicAlbumReleaseType"]` |
| `description()` | `?string` | `AlbumRelease.` |
| `SchemaEnumerationMember::memberNamesForEnumerationType($type)` | `array<int, string>`, the member names of an enumeration type | `["AlbumRelease", "BroadcastRelease", "EPRelease", ...]` for `MusicAlbumReleaseType` |
| `info()` | `?SchemaEnumerationMemberInfo` | |

## See also

- [Reading the schema.org vocabulary](Reading-the-schema-org-vocabulary.md): a walkthrough.
- [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md) and [Warming a host cache](Warming-a-host-cache.md).
