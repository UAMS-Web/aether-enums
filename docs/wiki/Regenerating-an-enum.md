Written against v0.2.1.

A source changed (a new ISO currency, a revised ISCO-08 index, a new schema.org release) and an enum has to be rebuilt from it.

The generators are development tools. Each writes into `src/` of the checkout it runs from, so run them where that checkout is the add-on's own: a Statamic host whose `vendor/uams-web/aether-enums` is a symlink to a checkout, as [`docs/DEVELOPMENT.md`](https://github.com/UAMS-Web/aether-enums/blob/main/docs/DEVELOPMENT.md) sets up. They are not meant to run against a host's real `vendor/` copy.

## Steps

1. Check that the host reads the checkout. In the host root, `vendor/uams-web/aether-enums` must be a symlink to the checkout you intend to change. Where Composer copied the package instead, a regeneration writes into the copy and the checkout never changes.

2. Prepare what the command reads. Most commands need nothing; three need a step:

   - `generate:zip-enum` reads the HUD USPS Crosswalk token from `config('services.hud.usps_api_token')`, which the host sets (for example in its `config/services.php`).
   - `generate:mime-type-enum` reads `node_modules/mime-db/db.json` in the add-on checkout, so run `npm ci` there first.
   - `generate:isco-08-enum` reads two workbooks. Pass `--local` for the copies in `src/Generation/Isco08/`, or `--definitions=` and `--index=` for your own files; with neither, the command downloads both.

3. Run the command, with the memory limit the large ones need:

   ```bash
   php artisan generate:state-enum
   php -d memory_limit=256M artisan generate:zip-enum
   php -d memory_limit=512M artisan generate:isco-08-enum --local
   ```

   Each command prints the path of every file it writes (`Wrote <path>`) and exits `0`. A failure the command handles, such as an unreachable source, prints the reason and exits `1`.

4. Review the result. `generate:county-enum`, `generate:mime-type-enum`, `generate:state-enum` and `generate:zip-enum` write a timestamped file (`State_generated_2026-11-24_09-30-00.php`, in the America/Chicago time zone) next to the committed enum, so nothing is overwritten. Compare it with the committed file, then rename it over the enum. The other five overwrite their enum in place, so read `git diff` instead.

5. Run `composer ci`. `tests/Unit/EnumParityTest.php` fails when a case name, a backing value or a case count differs from `tests/Fixtures/site-enum-manifest.json`, which is how an unintended change in a regeneration shows up.

**Warning:** the large enums are big files (`ZipCode.php` is about 6 MB). Review them with `git diff --stat` and spot checks rather than by reading the whole file.

## Reference

- [Generator commands](Generator-commands.md): every command's source, output, options and failure behavior.
- [Warming a host cache](Warming-a-host-cache.md): the hook `generate:schema-type-enum` offers a host.
- [Troubleshooting](Troubleshooting.md): a generated enum with no cases, and a command that finds no files.
