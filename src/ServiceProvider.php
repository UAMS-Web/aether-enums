<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Enums;

use Illuminate\Console\Command;
use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Enums\Console\GenerateCountyEnum;
use UAMSWeb\Aether\Enums\Console\GenerateCredentialRegistryEnum;
use UAMSWeb\Aether\Enums\Console\GenerateInternationalCodeDesignatorEnum;
use UAMSWeb\Aether\Enums\Console\GenerateIscedEnums;
use UAMSWeb\Aether\Enums\Console\GenerateIsco08Enum;
use UAMSWeb\Aether\Enums\Console\GenerateMimeTypeEnum;
use UAMSWeb\Aether\Enums\Console\GenerateSchemaTypeEnum;
use UAMSWeb\Aether\Enums\Console\GenerateStateEnum;
use UAMSWeb\Aether\Enums\Console\GenerateZipCodeEnum;

/**
 * Service provider for the add-on.
 *
 * The host application discovers it through `extra.laravel.providers` in
 * `composer.json`. Register the add-on's tags, fieldtypes, listeners, routes and
 * the like here, using the properties `AddonServiceProvider` documents, or in
 * `bootAddon()` for anything that needs Statamic booted first.
 */
class ServiceProvider extends AddonServiceProvider
{
    /**
     * The generator commands that rebuild the enums from their sources.
     *
     * @var list<class-string<Command>>
     */
    protected $commands = [
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateCountyEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateCredentialRegistryEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateInternationalCodeDesignatorEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateIscedEnums::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateIsco08Enum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateMimeTypeEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateSchemaTypeEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateStateEnum::class,
        // A property default holds no executable line, so coverage reports the item as uncovered although
        // tests/Integration/ServiceProviderTest.php pins the command it registers.
        // @pest-mutate-ignore: RemoveArrayItem
        GenerateZipCodeEnum::class,
    ];

    /**
     * The Vite build of the Control Panel assets. `input` and `publicDirectory`
     * must match `vite.config.js`; the built files are committed under
     * `resources/dist`.
     *
     * @var array{input: list<string>, publicDirectory: string}
     */
    // A property default holds no executable line, so coverage reports each item as uncovered
    // although tests/Unit/ViteBuildTest.php pins the whole array against vite.config.js.
    // @pest-mutate-ignore: RemoveArrayItem
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    /**
     * Boot the add-on once Statamic has booted.
     */
    public function bootAddon(): void
    {
        // Register the add-on's behavior here
    }
}
