# Aether Enums

The enums UAMS Statamic sites read, and the commands that regenerate them from their sources. The fieldtype add-on and the site both depend on this one add-on, so the enums have a single owner. It holds countries, currencies, languages, U.S. states and counties, ZIP codes, MIME types, schema.org vocabularies and several standard classifications. [The enums](#the-enums) lists them, and [Regenerating the enums](#regenerating-the-enums) covers the commands.

## Requirements

- PHP 8.4 or later
- Statamic 6.35 or later

## Installation

```bash
composer require uams-web/aether-enums
```

The add-on has no Control Panel screens and no settings. Installing it makes the enums and the generator commands available.

## The enums

Each enum lives in the `UAMSWeb\Aether\Enums` namespace and keeps the cases and values it had in the site's `App\Enums` namespace. `tests/Fixtures/site-enum-manifest.json` records, for each enum, its backing type, its case count and a hash of its cases and values, taken from the site's own files; `tests/Unit/EnumParityTest.php` fails when an enum drifts from it.

| Enum | Cases | What it lists |
| --- | --- | --- |
| `ApStyleGregorianMonth` | 12 | Gregorian months with AP Stylebook labels |
| `CanadianProvince` | 13 | Canadian provinces and territories |
| `Country` | 250 | ISO 3166-1 countries |
| `County` | 3243 | U.S. counties |
| `CredentialRegistry` | 120 | Credential Registry (CTDL) class terms |
| `Currency` | 154 | ISO 4217 currencies |
| `InternationalCodeDesignator` | 240 | ISO 6523 organization identifier schemes |
| `IscedA2011` | 66 | ISCED educational attainment levels |
| `IscedF2013` | 216 | ISCED fields of education and training |
| `IscedP2011` | 91 | ISCED education programme levels |
| `Isco08` | 619 | ISCO-08 occupation structure codes |
| `Language` | 8037 | ISO 639 languages |
| `LanguageBCP47` | 8268 | BCP 47 language tags |
| `MimeType` | 2564 | MIME types and their extensions |
| `SchemaDataType` | 20 | schema.org data types |
| `SchemaEnumerationMember` | 531 | schema.org enumeration members |
| `SchemaProperty` | 1521 | schema.org properties |
| `SchemaType` | 915 | schema.org types |
| `State` | 56 | U.S. states and territories |
| `ZipCode` | 39485 | U.S. ZIP codes |

The `Attributes` namespace holds the metadata classes the enums read their data through, and `Generation\EnumDataUtils` is the shared lookup the enums use.

## Regenerating the enums

Each generator writes into `src/` of a checkout of this add-on. `generate:county-enum`, `generate:mime-type-enum`, `generate:state-enum` and `generate:zip-enum` write a timestamped file (`County_generated_<date>_<time>.php`) to review and rename over the committed enum; the others overwrite their enum in place. They are development tools, run from the add-on's own checkout, and they are not meant to run from a host's `vendor/` directory.

| Command | Source | Writes |
| --- | --- | --- |
| `generate:county-enum` | TIGERweb counties layers | `County` |
| `generate:credential-registry-enum` | CredentialEngine fixtures | `CredentialRegistry` |
| `generate:international-code-designator-enum` | OpenPEPPOL ICD code list | `InternationalCodeDesignator` |
| `generate:isced-enums` | bundled UIS fixtures | `IscedA2011`, `IscedP2011`, `IscedF2013` |
| `generate:isco-08-enum` | ILO workbooks, bundled with `--local` | `Isco08` |
| `generate:mime-type-enum` | the Apache MIME list and `mime-db` | `MimeType` |
| `generate:schema-type-enum` | schema.org JSON-LD | `SchemaType`, `SchemaProperty`, `SchemaDataType`, `SchemaEnumerationMember` |
| `generate:state-enum` | TIGERweb states layer | `State` |
| `generate:zip-enum` | TIGERweb ZIP code layer | `ZipCode` |

`generate:zip-enum` reads the HUD USPS crosswalk token from `config('services.hud.usps_api_token')`, which the host or test environment must set. `generate:mime-type-enum` reads `node_modules/mime-db/db.json`, so run `npm ci` first. `generate:isco-08-enum --local` reads the two workbooks in `src/Generation/Isco08/`; without it, the command downloads them or takes `--definitions` and `--index` paths. `generate:isco-08-enum` reads its workbooks through `maatwebsite/excel`, which the add-on requires.

### Warming a host's cache after `generate:schema-type-enum`

A host that keeps a cache keyed on schema types can have `generate:schema-type-enum` warm it. Bind an implementation of `UAMSWeb\Aether\Enums\Generation\Schema\AliasCacheWarmer` in the container; the command calls `warm()` with the generated type names and reports that it did. With no binding the step is skipped.

### Analyzing the schema.org JSON-LD

`SchemaJsonLdStructureAnalyzer` reports the structure of the schema.org vocabulary `generate:schema-type-enum` reads: the value types seen under each key, the keys per node type, the `@id` prefixes and the distinct `@type` values (`src/Generation/Schema/schema-jsonld-normalization.md` describes the shape the generator relies on). It runs from the root of a checkout of this add-on, after `composer install`:

```bash
php scripts/analyze-schema-jsonld.php
php scripts/analyze-schema-jsonld.php path/to/vocabulary.jsonld
```

With no argument it reads `src/Generation/Schema/schemaorg-current-https.jsonld`. It writes `schema-jsonld-structure-report.txt` (or `.json` with `ANALYZE_OUTPUT=json`) and `schema-jsonld-all-ids.txt` into `src/Generation/Schema/`, and exits 1 when the input cannot be read or parsed. A host's dist install leaves `scripts/` out of the package, and where a source install puts it in the host's `vendor/` directory the add-on has no `vendor/` of its own, so the script refuses to run there rather than write into the host's `vendor/`.

## License

MIT. See [`LICENSE`](LICENSE).

## Development

[The development guide](docs/DEVELOPMENT.md) covers running the add-on against a host application, and [the submission checklist](docs/SUBMISSION.md) covers Packagist and the Marketplace. `composer ci` is the gate; the generators' tests write to a scratch directory, never to `src/`.
