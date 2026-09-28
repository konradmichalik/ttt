<?php

declare(strict_types=1);

/*
 * This file is part of the "ttt" Composer package.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSkip([
        // These files deliberately use string literals instead of ::class for classes that may not
        // exist: typo3/testing-framework is never a dependency, and #[RunClassInSeparateProcess]
        // was removed in PHPUnit 13. ::class there would break PHPStan's class.notFound check.
        StringClassNameToClassConstantRector::class => [
            __DIR__.'/src/Handler/FunctionalTestCaseGuard.php',
            __DIR__.'/src/Subscriber/ApplySandboxSubscriber.php',
            __DIR__.'/tests/src/Subscriber/ApplySandboxSubscriberTest.php',
        ],
    ])
;
