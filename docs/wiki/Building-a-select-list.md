Written against v0.2.1.

A site needs the options of a form field, such as a state dropdown, built from an enum rather than typed by hand.

## Steps

1. Loop over the enum's `cases()` and pair each case's `value` (what the form stores) with a getter (what the person reads):

   ```php
   use UAMSWeb\Aether\Enums\State;

   $options = [];

   foreach (State::cases() as $state) {
       $options[$state->value] = $state->fullName();
   }

   // ['AK' => 'Alaska', 'AL' => 'Alabama', 'AR' => 'Arkansas', ...]
   ```

   `State` has 56 cases (50 states, 5 territories and the District of Columbia). `State::cases()` keeps the order the enum declares, which is alphabetical by postal abbreviation, so `AK` comes before `AL`; sort by `fullName()` for an alphabetical list of names.

2. Choose the getter that names the option for the enum. `Country` uses `country_name()`, `Currency` uses `name()`, `Language` uses `name()` and `CanadianProvince` uses `fullName()`.

3. Validate what comes back with `tryFrom()`, which returns `null` for a value that is not a case:

   ```php
   $state = State::tryFrom($request->input('state'));

   abort_if($state === null, 422);
   ```

4. Store the case's `value`, not its label. A value is a stable code (`AR`); a label can change spelling in a later release.

## Where a case name stands in for the value

`MimeType`, `County` and `ZipCode` are backed by their case names, and an enum with thousands of cases (`ZipCode` has 39,485, `Language` 8,037) is too large for a dropdown. Use a lookup instead: [Finding a MIME type](Finding-a-MIME-type.md) for MIME types, and the [Geography enums](Geography-enums.md) reference for ZIP codes and counties.

## Reference

- [Geography enums](Geography-enums.md): `State`, `Country`, `CanadianProvince`, `County` and `ZipCode` getters.
- [Language enums](Language-enums.md): `Language` and `LanguageBCP47`.
- [Currency, MIME type and month enums](Currency-MIME-type-and-month-enums.md): `Currency` and `ApStyleGregorianMonth`.
