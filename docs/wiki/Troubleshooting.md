Written against v0.2.2.

One entry per symptom, as you meet it: the message or behavior, its cause, and the fix.

- [`"72201" is not a valid backing value for enum ...ZipCode`](#72201-is-not-a-valid-backing-value-for-enum-zipcode)
- [A regeneration left the committed enum unchanged](#a-regeneration-left-the-committed-enum-unchanged)
- [A regeneration changed nothing in the checkout](#a-regeneration-changed-nothing-in-the-checkout)
- [The generated `ZipCode` file has no cases](#the-generated-zipcode-file-has-no-cases)
- [The generated `MimeType` file lacks the `mime-db` types](#the-generated-mimetype-file-lacks-the-mime-db-types)
- [`generate:schema-type-enum` did not print `Warmed the alias cache of the host.`](#generateschema-type-enum-did-not-print-warmed-the-alias-cache-of-the-host)
- [The analyzer prints `Error: ... vendor/autoload.php not found`](#the-analyzer-prints-error--vendorautoloadphp-not-found)
- [Fixed](#fixed)

## "72201" is not a valid backing value for enum ZipCode

A lookup throws:

```text
ValueError: "72201" is not a valid backing value for enum UAMSWeb\Aether\Enums\ZipCode
```

**Cause:** `ZipCode`, `County` and `MimeType` are backed by their case names, not by the code or the type string. `MimeType::from('application/pdf')` fails the same way.

**Fix:** use the case name (`ZipCode::from('ZIP_72201')`, `County::from('AR_BENTON_COUNTY')`), or find the case another way: `MimeType::findByMimeType('application/pdf')` and `MimeType::findByExtension('pdf')`. `tryFrom()` returns `null` instead of throwing. See [Geography enums](Geography-enums.md).

## A regeneration left the committed enum unchanged

`generate:county-enum`, `generate:mime-type-enum`, `generate:state-enum` or `generate:zip-enum` printed `Wrote .../State_generated_2026-11-24_09-30-00.php`, and `State.php` did not change.

**Cause:** these four commands write a timestamped file next to the enum so that nothing is overwritten.

**Fix:** compare the new file with the committed enum, then rename it over the enum. See [Regenerating an enum](Regenerating-an-enum.md).

## A regeneration changed nothing in the checkout

A command printed `Wrote <path>`, and `git status` in the checkout is clean.

**Cause:** the host holds a copy of the add-on in `vendor/`, not a symlink to the checkout, so the command wrote into the copy.

**Fix:** point the host at the checkout as [`docs/DEVELOPMENT.md`](https://github.com/UAMS-Web/aether-enums/blob/main/docs/DEVELOPMENT.md) describes, and check that `vendor/uams-web/aether-enums` is a symlink before a run.

## The generated ZipCode file has no cases

`generate:zip-enum` succeeds and the file it wrote declares no cases.

**Cause:** `config('services.hud.usps_api_token')` is `null` or an empty string, so the command makes no HUD request and renders an empty enum. It reports no error.

**Fix:** set the token in the host's configuration and run the command again.

## The generated MimeType file lacks the mime-db types

`generate:mime-type-enum` succeeds, and the file has fewer cases than the committed `MimeType`.

**Cause:** `node_modules/mime-db/db.json` is missing, unreadable or not a JSON object, so the command builds the enum from the IANA and Apache lists alone. It reports no error.

**Fix:** run `npm ci` in the add-on checkout, then run the command again.

## generate:schema-type-enum did not print Warmed the alias cache of the host.

The command wrote its four files, but the `Warmed the alias cache of the host.` line is absent.

**Cause:** either no `AliasCacheWarmer` is bound in the host, or the bound one threw from `warm()`. The command discards that exception and still exits `0`.

**Fix:** bind the warmer, and check what `warm()` does when it fails. See [Warming a host cache](Warming-a-host-cache.md).

## The analyzer prints Error: ... vendor/autoload.php not found

`php scripts/analyze-schema-jsonld.php` prints:

```text
Error: <path>/vendor/autoload.php not found. Run this script from a checkout of uams-web/aether-enums after `composer install`; installed as a dependency it would write into the host's vendor/ directory.
```

**Cause:** the script is run where the add-on has no `vendor/` of its own, such as the copy inside a host's `vendor/`. It refuses so that it does not write its reports into the host's `vendor/`.

**Fix:** run it from a checkout of the add-on after `composer install`. See [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md).

## Fixed

Problems fixed in a release stay here, with the release that fixed them, instead of being deleted.

### v0.2.2

**Unknown field 'parent' for county.** `County::parent()` threw `ValueError: Unknown field 'parent' for county: AR_BENTON_COUNTY` for every one of the 3,243 cases, because it read a field the county data map never held ([#65](https://github.com/UAMS-Web/aether-enums/issues/65)). It returns the parent's USPS code (`AR`). On v0.2.1 or earlier, read `parentUspsCode` or `parentFipsCode` from the case's `CountyInfo` attribute, as [Metadata attributes](Metadata-attributes.md) shows.

**Unknown field 'iso639_1' for language.** A `Language` code getter (`iso639_1()`, `iso639_2_b()`, `iso639_2_t()`, `iso639_3()`, `iso639_5()`) threw `ValueError: Unknown field 'iso639_1' for language: aaa` for a case with no such code ([#66](https://github.com/UAMS-Web/aether-enums/issues/66)). The getters return `null` for an absent code, and their return type is `?string`. On v0.2.1 or earlier, read an optional code with `Language::fieldValueForLanguageCode($code, 'iso639_1')`, which returns `null`, or catch the `ValueError`. See [Language enums](Language-enums.md).
