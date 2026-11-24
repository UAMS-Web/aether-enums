Written against v0.2.1.

The five geography enums are `State`, `CanadianProvince`, `Country`, `County` and `ZipCode`, all in the `UAMSWeb\Aether\Enums` namespace. Each is a string-backed enum whose getters read data from an attribute on the case. Fields that no getter exposes are read from the attribute: see [Metadata attributes](Metadata-attributes.md).

| Enum | Cases | Backing value (what `from()` takes) | Example |
| --- | --- | --- | --- |
| `State` | 56 | Postal abbreviation | `State::from('AR')` |
| `CanadianProvince` | 13 | Canada Post two-letter abbreviation | `CanadianProvince::from('ON')` |
| `Country` | 250 | ISO 3166-1 alpha-2 code | `Country::from('CA')` |
| `County` | 3,243 | The case name (state, name and type, in capitals) | `County::from('AR_BENTON_COUNTY')` |
| `ZipCode` | 39,485 | The case name, `ZIP_` and the five digits | `ZipCode::from('ZIP_72201')` |

`from()` throws a `ValueError` for a value that is not a case, and `tryFrom()` returns `null`. A bare ZIP code such as `72201` is not a backing value of `ZipCode`.

## State

56 cases: 50 states, 5 territories and the District of Columbia. `State::cases()` lists them in order of postal abbreviation.

| Method | Returns | Example for `State::AR` |
| --- | --- | --- |
| `fullName()` | `string` | `Arkansas` |
| `apAbbreviation()` | `string`, the AP Stylebook abbreviation | `Ark.` |
| `type()` | `string`: `state`, `territory` or `district` | `state` |
| `fipsCode()` | `string`, the two-digit FIPS code | `05` |
| `iso3166_2()` | `string`, the ISO 3166-2 subdivision code | `US-AR` |
| `postal()` | `string`, the postal abbreviation | `AR` |

## CanadianProvince

13 provinces and territories, backed by the Canada Post abbreviation (`AB`, `BC`, `MB`, `NB`, `NL`, `NS`, `NT`, `NU`, `ON`, `PE`, `QC`, `SK`, `YT`).

| Method | Returns | Example for `CanadianProvince::ON` |
| --- | --- | --- |
| `fullName()` | `string` | `Ontario` |
| `postal()` | `string`, the abbreviation | `ON` |

## Country

| Method | Returns | Example for `Country::US` |
| --- | --- | --- |
| `country_name()` | `string`, the English name | `United States of America` |
| `alpha2()` | `string`, the ISO 3166-1 alpha-2 code (the backing value) | `US` |
| `alpha3()` | `string` | `USA` |
| `numeric()` | `string`, the ISO 3166-1 numeric code, with leading zeros kept | `840` |
| `requiresThe()` | `bool`, whether a sentence puts "the" before the name | `true` |
| `sentenceName()` | `string`, the name with "the " in front when `requiresThe()` is true | `the United States of America` |

## County

3,243 U.S. counties and county equivalents. The backing value is the case name, so use `tryFrom()` with the case name or loop over `County::cases()`.

| Method | Returns | Example for `County::AR_BENTON_COUNTY` |
| --- | --- | --- |
| `fullName()` | `string` | `Benton County` |
| `fipsCode()` | `string`, the three-digit county part of the FIPS code (without the state) | `007` |
| `parent()` | `?string`, documented as the parent state | throws `ValueError` in v0.2.1 |

**Warning:** `County::parent()` throws a `ValueError` (`Unknown field 'parent' for county: ...`) for every case in v0.2.1; it is tracked in [#65](https://github.com/UAMS-Web/aether-enums/issues/65). To read the parent state, read `parentUspsCode` or `parentFipsCode` from the case's `CountyInfo` attribute, as [Metadata attributes](Metadata-attributes.md) shows. The docblock on the enum names more getters than exist; only `fullName()`, `fipsCode()` and `parent()` are defined.

## ZipCode

39,485 ZIP codes, each with the city and the state and counties it falls in.

| Method | Returns | Example for `ZipCode::ZIP_72201` |
| --- | --- | --- |
| `zipCode()` | `string`, the five digits | `72201` |
| `city()` | `string` | `LITTLE ROCK` |
| `stateUsps()` | `string`, the state's postal abbreviation | `AR` |
| `stateGeoId()` | `string`, the state's Census geographic identifier | `05` |
| `countyGeoIds()` | `array<int, string>`, five-digit county identifiers | `["05119"]` |
| `toArray()` | `array` with the keys `zip_code`, `city`, `state_usps`, `state_geoId` and `county_geoId` | |

## See also

- [Your first lookup](Your-first-lookup.md) and [Building a select list](Building-a-select-list.md).
- [Regenerating an enum](Regenerating-an-enum.md): `generate:state-enum`, `generate:county-enum` and `generate:zip-enum` rebuild these from TIGERweb and HUD.
