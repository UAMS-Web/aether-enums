Written against v0.2.1.

By the end of this page you will have read a U.S. state, a country and a currency from their enums, and you will know the one idea every enum here shares: a case has a **backing value** (a code, or an identifier) and **getter methods** that return its other facts.

It assumes the add-on is installed (see [Installation (README)](https://github.com/UAMS-Web/aether-enums#installation)) in a Statamic site, and that the code below runs in the site's PHP, for example in `php artisan tinker`.

## 1. Read a state

`State` is backed by the postal abbreviation, so a form value such as `AR` becomes a case with `from()`:

```php
use UAMSWeb\Aether\Enums\State;

$state = State::from('AR');

$state->fullName();      // "Arkansas"
$state->apAbbreviation(); // "Ark."
$state->type();          // "state"
$state->fipsCode();      // "05"
$state->iso3166_2();     // "US-AR"
```

`State::tryFrom('XX')` returns `null` instead of throwing, which suits a value that comes from user input.

## 2. Read a country

`Country` is backed by the ISO 3166-1 alpha-2 code:

```php
use UAMSWeb\Aether\Enums\Country;

$country = Country::from('CA');

$country->country_name();  // "Canada"
$country->alpha3();        // "CAN"
$country->numeric();       // a string, so a leading zero survives
$country->sentenceName();  // the name, prefixed with "the" where a sentence needs it
```

`Country::US->sentenceName()` is `the United States of America`, and `requiresThe()` is the flag behind it.

## 3. Read a currency

`Currency` is backed by the ISO 4217 code, and it carries what is needed to format an amount:

```php
use UAMSWeb\Aether\Enums\Currency;

Currency::JPY->name();        // "Yen"
Currency::JPY->minor_unit();  // 0
Currency::USD->symbol();      // "$"
```

**Note:** a case value is not always the label. `MimeType`, `County` and `ZipCode` are backed by their case names (`IMAGE_PNG`, `AR_BENTON_COUNTY`, `ZIP_72201`), so `ZipCode::from('72201')` throws a `ValueError`. Each enum's backing value is listed on its reference page.

## Go further

- [Building a select list](Building-a-select-list.md): use the cases as the options of a form field.
- [Geography enums](Geography-enums.md): every getter of `State`, `Country` and the other geography enums.
- [Metadata attributes](Metadata-attributes.md): the fields a getter does not expose.
