<?php

/**
 * Pest configuration for the add-on.
 *
 * Integration tests boot a minimal Statamic through Orchestra Testbench via
 * `Tests\TestCase`; Unit tests stay on PHPUnit's own base class and boot
 * nothing. Shared helpers and custom expectations go here when the suite
 * grows them.
 */

declare(strict_types=1);

use Pest\Mutate\Repositories\ConfigurationRepository;
use Pest\Support\Container;
use UAMSWeb\Aether\Enums\Tests\TestCase;

pest()->extend(TestCase::class)->in('Integration');

require_once __DIR__.'/Support/PendingArtisan.php';
require_once __DIR__.'/Support/TypeNarrowing.php';

require_once __DIR__.'/Support/MutationScope.php';
require_once __DIR__.'/Support/ScratchEnumPaths.php';
require_once __DIR__.'/Support/LocalHttpServer.php';

// The generated enums are excluded from coverage, analysis and refactoring, and from mutation
// here: a mutation run over the largest of them (`src/ZipCode.php`, 6 MB) never finishes.
// This reaches a run over a directory (`composer mutate`, a scheduled or dispatched CI run); a
// file named with `--path` bypasses it, so `ci.yml` excludes the same files from the changed
// files it selects. `tests/Unit/MutationScopeTest.php` keeps the lists equal.
if (class_exists(ConfigurationRepository::class)) {
    /** @var ConfigurationRepository $mutationConfiguration */
    $mutationConfiguration = Container::getInstance()->get(ConfigurationRepository::class);
    $mutationConfiguration->globalConfiguration('default')->ignore(mutationIgnoredPaths());
}
