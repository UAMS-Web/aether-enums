Written against the unreleased `main` branch.

Everything in this add-on lives in the `UAMSWeb\Aether\Enums` namespace. There are no configuration keys.

## Enums

The [README](https://github.com/UAMS-Web/aether-enums#the-enums) lists the twenty enums with their case counts. Each is a backed enum in `src/`, and its metadata is read through the classes in `UAMSWeb\Aether\Enums\Attributes`.

## Commands

| Command | Writes |
| --- | --- |
| `generate:county-enum` | `County` |
| `generate:credential-registry-enum` | `CredentialRegistry` |
| `generate:international-code-designator-enum` | `InternationalCodeDesignator` |
| `generate:isced-enums` | `IscedA2011`, `IscedP2011`, `IscedF2013` |
| `generate:isco-08-enum` | `Isco08` |
| `generate:mime-type-enum` | `MimeType` |
| `generate:schema-type-enum` | `SchemaType`, `SchemaProperty`, `SchemaDataType`, `SchemaEnumerationMember` |
| `generate:state-enum` | `State` |
| `generate:zip-enum` | `ZipCode` |

## Contract

`UAMSWeb\Aether\Enums\Generation\Schema\AliasCacheWarmer` is the one interface a host can implement. Bind it in the container and `generate:schema-type-enum` calls `warm()` with the generated type names.
