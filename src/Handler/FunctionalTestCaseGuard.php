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

namespace KonradMichalik\Ttt\Handler;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function class_exists;
use function debug_backtrace;
use function is_subclass_of;
use function sprintf;

/**
 * FunctionalTestCaseGuard.
 *
 * Fails loudly when a handler is applied for a test that extends
 * typo3/testing-framework's FunctionalTestCase, for attributes that are
 * structurally incompatible with it (the framework already owns Environment
 * initialization and the compiled DI container by the time Terrarium would
 * apply). typo3/testing-framework is never a dependency of this package -
 * the class_exists() guard keeps this inert when it is absent.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FunctionalTestCaseGuard
{
    // String literal, not ::class: typo3/testing-framework is never a
    // dependency of this package, so the class may not exist to reference.
    private const FUNCTIONAL_TEST_CASE = 'TYPO3\TestingFramework\Core\Functional\FunctionalTestCase';

    private function __construct() {}

    public static function assertNotFunctionalTestCase(string $attributeName, string $reason, string $alternative): void
    {
        if (!class_exists(self::FUNCTIONAL_TEST_CASE)) {
            return;
        }

        $testClassName = self::currentTestClassName();

        if (null !== $testClassName && is_subclass_of($testClassName, self::FUNCTIONAL_TEST_CASE)) {
            throw new RuntimeException(sprintf('%s cannot be used on FunctionalTestCase (%s): %s. %s', $attributeName, $testClassName, $reason, $alternative), 1753900001);
        }
    }

    private static function currentTestClassName(): ?string
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $object = $frame['object'] ?? null;

            if ($object instanceof TestCase) {
                return $object::class;
            }
        }

        return null;
    }
}
