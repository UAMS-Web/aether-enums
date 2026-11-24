Written against v0.2.1.

Everything in this add-on lives in the `UAMSWeb\Aether\Enums` namespace (`src/`). This page lists what the add-on registers with Statamic and Laravel, then indexes the reference pages for the enums and the commands.

## What the add-on registers

The host discovers `UAMSWeb\Aether\Enums\ServiceProvider` through `extra.laravel.providers` in `composer.json`. The provider extends Statamic's `AddonServiceProvider` and registers two things:

| Item | What it is |
| --- | --- |
| Commands | The nine `generate:*` commands: `generate:county-enum`, `generate:credential-registry-enum`, `generate:international-code-designator-enum`, `generate:isced-enums`, `generate:isco-08-enum`, `generate:mime-type-enum`, `generate:schema-type-enum`, `generate:state-enum` and `generate:zip-enum`. See [Generator commands](Generator-commands.md). |
| Control Panel assets | A Vite build of `resources/js/cp.js` and `resources/css/cp.css`, committed under `resources/dist`. Both files are empty placeholders: the script registers nothing in the Control Panel and the stylesheet has no rules. `php artisan vendor:publish --tag=aether-enums` copies the build to `public/vendor/aether-enums/build`. |

The add-on registers none of the following, so there is nothing to configure or call: configuration keys, routes, Antlers tags, permissions, Control Panel screens or navigation, fieldtypes, listeners, and editions. The enums are plain PHP classes that a host's code, or another add-on, uses directly.

The one contract a host can fulfill is the `AliasCacheWarmer` interface: see [Warming a host cache](Warming-a-host-cache.md).

## The enums

The [README](https://github.com/UAMS-Web/aether-enums#the-enums) lists the twenty enums with their case counts and what each lists. Each is a backed enum in `src/`, and each case carries its data in an attribute that its getter methods read. The pages below document every public method, and the backing value of each enum.

- [Geography enums](Geography-enums.md): `State`, `CanadianProvince`, `Country`, `County` and `ZipCode`.
- [Language enums](Language-enums.md): `Language` and `LanguageBCP47`.
- [Classification enums](Classification-enums.md): `IscedA2011`, `IscedP2011`, `IscedF2013`, `Isco08`, `CredentialRegistry` and `InternationalCodeDesignator`.
- [Schema.org enums](Schema-org-enums.md): `SchemaType`, `SchemaProperty`, `SchemaDataType` and `SchemaEnumerationMember`.
- [Currency, MIME type and month enums](Currency-MIME-type-and-month-enums.md): `Currency`, `MimeType` and `ApStyleGregorianMonth`.
- [Metadata attributes](Metadata-attributes.md): the `Info` attribute classes, reading a field a getter does not expose, and `EnumDataUtils`.

## The commands and scripts

- [Generator commands](Generator-commands.md): the nine commands that rebuild the enums.
- [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md): `scripts/analyze-schema-jsonld.php`.
