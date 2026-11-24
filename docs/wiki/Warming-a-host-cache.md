Written against v0.2.1.

A host keeps a cache keyed on schema.org types and wants it refreshed whenever `generate:schema-type-enum` regenerates the type list.

The add-on has no cache of its own. It defines one interface, `UAMSWeb\Aether\Enums\Generation\Schema\AliasCacheWarmer`, with one method, and calls it after the enum files are written.

## Steps

1. Implement the interface in the host:

   ```php
   use UAMSWeb\Aether\Enums\Generation\Schema\AliasCacheWarmer;

   final class HostAliasCacheWarmer implements AliasCacheWarmer
   {
       /**
        * @param  list<string>  $typeNames  The schema type names in the regenerated enum.
        */
       public function warm(array $typeNames): void
       {
           // Rebuild the host's cache for these types
       }
   }
   ```

2. Bind it in the host's service container, for example in a service provider's `register()` method:

   ```php
   $this->app->bind(AliasCacheWarmer::class, HostAliasCacheWarmer::class);
   ```

3. Run `php artisan generate:schema-type-enum`. After the four enum files are written, the command calls `warm()` with the generated type names, and prints `Warmed the alias cache of the host.` when the call returns.

**Note:** with no binding, the step is skipped and nothing is printed about it.

**Warning:** a `warm()` that throws does not fail the command. The enum files are already written, the exception is discarded, and the `Warmed` line is not printed, so its absence is the only sign that the warmer failed.

## Reference

- [Reference](Reference.md): the contract among the things the add-on registers.
- [Generator commands](Generator-commands.md): `generate:schema-type-enum` and its output.
