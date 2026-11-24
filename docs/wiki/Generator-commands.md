Written against v0.2.1.

The add-on registers nine Artisan commands that rebuild the enums from their sources. They are development tools: run them from a host whose `vendor/uams-web/aether-enums` is a symlink to a checkout of this add-on, so that the output lands in the checkout's `src/`. [Regenerating an enum](Regenerating-an-enum.md) walks through a run.

| Command | Source | Writes | Output | Options |
| --- | --- | --- | --- | --- |
| `generate:county-enum` | TIGERweb counties layers | `County` | A timestamped file | None |
| `generate:credential-registry-enum` | CredentialEngine fixtures, and the credreg.net CTDL JSON term documents | `CredentialRegistry` | Overwrites `src/CredentialRegistry.php` | None |
| `generate:international-code-designator-enum` | OpenPEPPOL ICD code list (XML) | `InternationalCodeDesignator` | Overwrites in place | None |
| `generate:isced-enums` | UIS fixtures bundled in `src/Generation/Isced/` | `IscedA2011`, `IscedP2011`, `IscedF2013` | Overwrites in place | `--isced-f=` |
| `generate:isco-08-enum` | ILO ISCO-08 workbooks | `Isco08` | Overwrites `src/Isco08.php` | `--local`, `--definitions=`, `--index=` |
| `generate:mime-type-enum` | `mime-db`, the IANA media types and the Apache MIME list | `MimeType` | A timestamped file | None |
| `generate:schema-type-enum` | schema.org JSON-LD, downloaded from `https://schema.org/version/latest/schemaorg-current-https.jsonld` | `SchemaType`, `SchemaProperty`, `SchemaDataType`, `SchemaEnumerationMember` | Overwrites the four files | None |
| `generate:state-enum` | TIGERweb states layer | `State` | A timestamped file | None |
| `generate:zip-enum` | HUD USPS Crosswalk API | `ZipCode` | A timestamped file | `--limit=` |

**Timestamped output:** `generate:county-enum`, `generate:mime-type-enum`, `generate:state-enum` and `generate:zip-enum` write `<Enum>_generated_<YYYY-MM-DD>_<HH-MM-SS>.php` into `src/`, with the time in the America/Chicago zone, and leave the committed enum alone. The other commands overwrite the enum.

Every command prints the path of each file it writes (`Wrote <path>`, or `Output file: <path>` for `generate:international-code-designator-enum`) and exits `0`.

## Options and requirements

- **`generate:isced-enums --isced-f=<path>`** reads the ISCED-F 2013 rows from another JSON file instead of the bundled `isced-f-2013.json`. A path that is not readable prints `ISCED-F JSON file is not readable: <path>` and exits `1`. The command prints which fixture it used.
- **`generate:isco-08-enum`** has three modes. `--local` reads the two `.xlsx` workbooks in `src/Generation/Isco08/` (`ISCO-08 EN Structure and definitions.xlsx` and `ISCO-08 -88 EN Index.xlsx`); `--definitions=<path>` and `--index=<path>` read workbooks you supply; with none of them the command downloads both from the ILO. It reads the workbooks through `maatwebsite/excel`, which the add-on requires. If a file is missing or unreadable the command prints the path (`Local fixtures not found or not readable. Expected files:` for `--local`) and exits `1`. Run it with `php -d memory_limit=512M`.
- **`generate:mime-type-enum`** reads `node_modules/mime-db/db.json` in the add-on checkout, so run `npm ci` there first. When that file is missing, unreadable or not a JSON object, the command carries on with the IANA and Apache lists alone and reports no error.
- **`generate:zip-enum --limit=<n>`** stops after `n` ZIP codes, which is useful for a trial run. The command reads the HUD bearer token from `config('services.hud.usps_api_token')`. When the token is absent or empty it makes no HUD request and writes a file with no cases, without an error. Run it with `php -d memory_limit=256M`.
- **`generate:county-enum` and `generate:state-enum`** print the reason and exit `1` when the TIGERweb request fails (`Failed to fetch TIGERweb layer 1 counties`, `Failed to fetch TIGERweb states`).
- **`generate:international-code-designator-enum`** prints `Failed to fetch or parse ICD XML` and exits `1` when the code list cannot be read.
- **`generate:schema-type-enum`** calls the host's `AliasCacheWarmer` when one is bound: see [Warming a host cache](Warming-a-host-cache.md). It stops with an exception, rather than an exit code, when the JSON-LD cannot be downloaded (`Failed to fetch schema.org JSON-LD from <url>`) or has no `@graph` (`Invalid schema.org JSON-LD: missing @graph`).
- **`generate:credential-registry-enum`** fetches its fixtures over the network and stops with `Failed to fetch: <url>` when one cannot be read.

## See also

- [Troubleshooting](Troubleshooting.md): a ZIP code file with no cases, and a MIME type file without `mime-db` data.
- [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md): the script that reports on the file `generate:schema-type-enum` reads.
