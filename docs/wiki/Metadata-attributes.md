Written against v0.2.2.

Each enum case carries its data in a PHP attribute, and the enum's getter methods read that attribute. Eighteen attribute classes hold the data: eleven in the `UAMSWeb\Aether\Enums` namespace beside their enums, and seven in `UAMSWeb\Aether\Enums\Attributes`. The three ISCED enums share `IscedInfo`. Each class is a plain value object whose constructor properties are the fields below, in the order shown.

| Attribute class | Enum | Fields |
| --- | --- | --- |
| `ApStyleMonthInfo` | `ApStyleGregorianMonth` | `prose`, `tabular` |
| `CanadianProvinceInfo` | `CanadianProvince` | `fullName` |
| `CountryInfo` | `Country` | `country_name`, `alpha3`, `numeric`, `useThe` |
| `CountyInfo` | `County` | `fullName`, `fipsCode`, `baseName`, `parentUspsCode`, `parentFipsCode`, `geoId`, `nationalStandardCode`, `fipsClassCode`, `functionalStatusCode`, `mafTigerFeatureClassCode`, `mafTigerObjectIdentifier`, `legalStatisticalAreaDescriptionCode`, `areaLand`, `areaWater`, `arcGisObjectIdentifier`, `centroidGeo`, `internalPointGeo` |
| `CurrencyInfo` | `Currency` | `name`, `numeric`, `symbol`, `symbol_placement`, `symbol_separation`, `minor_unit` |
| `IcdInfo` | `InternationalCodeDesignator` | `id`, `name`, `description` |
| `LanguageBCP47Info` | `LanguageBCP47` | `type`, `description`, `added`, `scope`, `comments`, `macrolanguage`, `deprecated`, `preferredvalue`, `suppressscript` |
| `LanguageInfo` | `Language` | `name`, `scope`, `type`, `iso639_1`, `iso639_2_b`, `iso639_2_t`, `iso639_3`, `iso639_5`, `alternate_names` |
| `MimeTypeInfo` | `MimeType` | `mimeType`, `extensions`, `category` |
| `StateInfo` | `State` | `fullName`, `apAbbreviation`, `type`, `fipsCode`, `iso3166_2`, `baseName`, `geoId`, `nationalStandardCode`, `division`, `region`, `functionalStatusCode`, `mafTigerFeatureClassCode`, `mafTigerObjectIdentifier`, `legalStatisticalAreaDescriptionCode`, `areaLand`, `areaWater`, `arcGisObjectIdentifier`, `centroidGeo`, `internalPointGeo` |
| `ZipCodeInfo` | `ZipCode` | `zip_code`, `city`, `state_usps`, `state_geoId`, `county_geoId` |
| `Attributes\CredentialRegistryTypeInfo` | `CredentialRegistry` | see [Classification enums](Classification-enums.md) |
| `Attributes\IscedInfo` | `IscedA2011`, `IscedP2011`, `IscedF2013` | `code`, `label`, `parent`, `ancestors`, `children`, `descendants`, `tier` |
| `Attributes\Isco08Info` | `Isco08` | `level`, `code`, `major_group`, `sub_major_group`, `minor_group`, `unit_group`, `title`, `definition`, `tasks_include`, `excluded_occupations`, `notes`, `occupational_titles`, `parent`, `ancestors`, `children`, `descendants` |
| `Attributes\SchemaDataTypeInfo` | `SchemaDataType` | `rdfs_comment` |
| `Attributes\SchemaEnumerationMemberInfo` | `SchemaEnumerationMember` | `type`, `rdfs_comment` |
| `Attributes\SchemaPropertyInfo` | `SchemaProperty` | `rdfs_comment`, `schema_domain_includes`, `range_includes`, `superseded_by` |
| `Attributes\SchemaTypeInfo` | `SchemaType` | `rdfs_comment`, `rdfs_sub_class_of`, `children`, `ancestors`, `descendants`, `property_names`, `slug`, `superseded_by`, `members` |

## Reading a field a getter does not expose

A getter exists only for the commonly used fields. Read any other field from the case's attribute with reflection:

```php
use UAMSWeb\Aether\Enums\Country;
use UAMSWeb\Aether\Enums\CountryInfo;

$info = (new \ReflectionEnumUnitCase(Country::class, 'US'))
    ->getAttributes(CountryInfo::class)[0]
    ->newInstance();

$info->alpha3;   // "USA"
$info->useThe;   // true
```

The same pattern reads any field that has no getter, such as the FIPS code of a county's state:

```php
use UAMSWeb\Aether\Enums\County;
use UAMSWeb\Aether\Enums\CountyInfo;

$info = (new \ReflectionEnumUnitCase(County::class, 'AR_BENTON_COUNTY'))
    ->getAttributes(CountyInfo::class)[0]
    ->newInstance();

$info->parentFipsCode;  // "05"
```

The enums with an `info()` method (`CredentialRegistry` and the four schema.org enums) return the attribute directly, or `null`.

## EnumDataUtils

`UAMSWeb\Aether\Enums\Generation\EnumDataUtils` is the shared lookup the enums' getters use. It builds, once per enum, a map from case value to the attribute's fields, and reads one field from it. A host does not need it; it matters when a getter throws.

| Method | Purpose |
| --- | --- |
| `buildLazyDataMap($enumClass, $attributeClass, $dataExtractor)` | Builds and caches the map by reading each case's attribute through reflection |
| `getFieldValue($dataMap, $caseValue, $fieldName, $enumName)` | Returns a field, and throws a `ValueError` when the case is unknown (`Unknown <enum>: <value>`) or the field is unset (`Unknown field '<field>' for <enum>: <value>`); a `null` field counts as unset |
| `getBooleanFieldValue(..., bool $defaultValue = false)` | Returns a boolean field, or the default when the field is unset |
| `getIntegerFieldValue(...)` | Returns an integer field |
| `getArrayFieldValue(...)` | Returns an array field |
| `createFieldAccessor(...)`, `createBooleanFieldAccessor(...)` | Return a closure that takes a case value and returns the field |

## See also

- [Troubleshooting](Troubleshooting.md): the `Unknown field` errors that v0.2.2 fixed.
