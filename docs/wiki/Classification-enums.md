Written against v0.2.1.

Six enums list standard classifications: three ISCED editions, ISCO-08, the Credential Registry class terms and the ISO 6523 identifier schemes. All are in the `UAMSWeb\Aether\Enums` namespace.

| Enum | Cases | Backing value (what `from()` takes) | Case name | Source |
| --- | --- | --- | --- | --- |
| `IscedA2011` | 66 | The ISCED code (`5`) | `ISCEDA_` and the code | ISCED 2011, educational attainment |
| `IscedP2011` | 91 | The ISCED code (`5`) | `ISCEDP_` and the code | ISCED 2011, education programmes |
| `IscedF2013` | 216 | The ISCED-F code (`0511`) | `ISCEDF_` and the code | ISCED-F 2013, fields of education and training |
| `Isco08` | 619 | The structure code, one to four digits (`2211`) | `ISCO08_` and the code | ILO ISCO-08 |
| `CredentialRegistry` | 120 | The CTDL term (`ceterms:AccreditAction`) | The term in camel case, no colon (`CetermsAccreditAction`) | CredentialEngine CTDL |
| `InternationalCodeDesignator` | 240 | The four-digit ICD code (`0060`) | `ICD_` and the code (`ICD_0060`) | OpenPEPPOL ISO 6523 list |

## ISCED enums

`IscedA2011`, `IscedP2011` and `IscedF2013` share one set of methods. Codes form a tree: a top-level code has no parent.

| Method | Returns | Example for `IscedF2013::from('0511')` |
| --- | --- | --- |
| `code()` | `string` | `0511` |
| `label()` | `string` | `Biology` |
| `parent()` | `?string`, the parent's code | `051`; `null` for a top-level code |
| `ancestors()` | `array<int, string>`, broadest first | `["05", "051"]` |
| `children()` | `array<int, string>`, the codes one level down | `[]` for a leaf |
| `descendants()` | `array<int, string>`, every code below | `[]` for a leaf |
| `tier()` | `int`, the depth, starting at 1 for a top-level code | `3` |
| `labelsWithAncestors(?string $separator = null)` | `array<int, string>`, the labels from the broadest ancestor to the case, or a `string` joined by `$separator` | `["Natural sciences, mathematics and statistics", "Biological and related sciences", "Biology"]` |

A walk-through is in [Walking a classification hierarchy](Walking-a-classification-hierarchy.md).

## Isco08

| Method | Returns | Example for `Isco08::from('2211')` |
| --- | --- | --- |
| `level()` | `int`, 1 to 4 (the number of digits) | `4` |
| `code()` | `string` | `2211` |
| `majorGroup()` | `string` | `2` |
| `subMajorGroup()` | `?string` | `22` |
| `minorGroup()` | `?string` | `221` |
| `unitGroup()` | `?string` | `2211` |
| `title()` | `string` | `Generalist Medical Practitioners` |
| `definition()` | `string` | the ILO definition text |
| `tasksInclude()` | `string` | the ILO list of tasks |
| `excludedOccupations()` | `?string` | `null` when there are none |
| `notes()` | `?string` | `null` when there are none |
| `occupationalTitles()` | `array<int, string>`, the index titles under the code | |
| `parent()`, `ancestors()`, `children()`, `descendants()` | as for ISCED | `ancestors()` is `["2", "22", "221"]` |

Three static helpers support cascading selects and storage:

| Method | Returns |
| --- | --- |
| `Isco08::hierarchyOptionRows()` | `array` with `majors`, the ten level-1 groups, and `children_by_parent`, each code's direct children keyed by the parent's code. A row has `key` (the code), `value` (`"{code} {title}"`) and `description` (the definition). |
| `Isco08::tierStoragePayloadForCode(string $code)` | `?array` with `major_group`, `sub_major_group`, `minor_group` and `unit_group` for a one- to four-digit code, or `null` for an unknown code |
| `Isco08::resolvedDeepestStructureCode(array $partialTiers)` | `?string`, the deepest code in a map of the same four keys, stopping at the first empty tier |

## CredentialRegistry

120 Credential Transparency Description Language class terms. `info()` returns the case's `CredentialRegistryTypeInfo` attribute, or `null`. Its fields are `slug`, `uri`, `shorthand_uri`, `label`, `definition`, `comment`, `usage_note`, `term_type`, `status`, `same_as`, `subclass_of`, `children`, `ancestors`, `descendants`, `property_names` and `in_range_of`. For `CredentialRegistry::CetermsAccreditAction`, `label` is `Accredit Action`, `slug` is `AccreditAction` and `subclass_of` is `ceterms:CredentialingAction`. A field the source does not carry is an empty string.

## InternationalCodeDesignator

| Method | Returns | Example for `InternationalCodeDesignator::ICD_0007` |
| --- | --- | --- |
| `id()` | `string`, the four-digit code | `0007` |
| `name()` | `string`, the scheme's official name | `Organisationsnummer` |
| `description()` | `string`, the notes on use and the issuing agency | `Notes on Use of Code: ...` |

## See also

- [Metadata attributes](Metadata-attributes.md): `IscedInfo`, `Isco08Info`, `CredentialRegistryTypeInfo` and `IcdInfo`.
- [Regenerating an enum](Regenerating-an-enum.md): the commands that rebuild these.
