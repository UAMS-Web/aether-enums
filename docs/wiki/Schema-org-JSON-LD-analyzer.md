Written against v0.2.1.

`scripts/analyze-schema-jsonld.php` reports the structure of the schema.org vocabulary that `generate:schema-type-enum` reads. It is for a person checking whether a new schema.org release changed the shape the generator relies on. [`src/Generation/Schema/schema-jsonld-normalization.md`](https://github.com/UAMS-Web/aether-enums/blob/main/src/Generation/Schema/schema-jsonld-normalization.md) describes that shape.

## What it reports

The analyzer class is `UAMSWeb\Aether\Enums\Generation\Schema\SchemaJsonLdStructureAnalyzer`. It reports:

- the value types seen under each key,
- the keys used by each node type,
- the `@id` prefixes, and
- the distinct `@type` values.

## Running it

Run it from the root of a checkout of this add-on, after `composer install`:

```bash
php scripts/analyze-schema-jsonld.php
php scripts/analyze-schema-jsonld.php path/to/vocabulary.jsonld
```

With no argument it reads `src/Generation/Schema/schemaorg-current-https.jsonld`.

| Output | Where |
| --- | --- |
| `schema-jsonld-structure-report.txt`, or `schema-jsonld-structure-report.json` when the environment variable `ANALYZE_OUTPUT` is `json` | `src/Generation/Schema/` |
| `schema-jsonld-all-ids.txt` | `src/Generation/Schema/` |

The exit status is `0` on success and `1` when the input cannot be read or parsed.

## Where it refuses to run

The script writes into `src/Generation/Schema/` of the add-on that holds it. It looks for `vendor/autoload.php` in the add-on's own directory, and when that file is missing it prints an error and exits `1` rather than write into a host's `vendor/` directory: a host's dist install leaves `scripts/` out of the package, and where a source install puts the script in the host's `vendor/` directory the add-on has no `vendor/` of its own. See [Troubleshooting](Troubleshooting.md) for the message.

## See also

- [Generator commands](Generator-commands.md): `generate:schema-type-enum`.
- [Schema.org enums](Schema-org-enums.md): what the generator produces.
