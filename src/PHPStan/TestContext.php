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

namespace KonradMichalik\Ttt\PHPStan;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\TestCase;

/**
 * TestContext.
 *
 * What the rules need to know about the test they are looking at.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class TestContext
{
    // Unit tests only: ttt rejects some attributes on FunctionalTestCase.
    private const FUNCTIONAL_TEST_CASE = 'TYPO3\TestingFramework\Core\Functional\FunctionalTestCase';

    public function isUnitTest(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        return null !== $class && $class->is(TestCase::class) && !$class->is(self::FUNCTIONAL_TEST_CASE);
    }

    // ttt restores the whole global afterwards, so setting more state inside such a test is safe.
    // It collects attributes from the class, its parents and the test method.
    /**
     * @param class-string $attribute
     */
    public function isSandboxed(Scope $scope, string $attribute): bool
    {
        $class = $scope->getClassReflection();
        if (null === $class) {
            return false;
        }

        foreach ([$class, ...$class->getParents()] as $candidate) {
            if ([] !== $candidate->getNativeReflection()->getAttributes($attribute)) {
                return true;
            }
        }

        $function = $scope->getFunction();
        $native = $class->getNativeReflection();

        return null !== $function
            && $native->hasMethod($function->getName())
            && [] !== $native->getMethod($function->getName())->getAttributes($attribute);
    }
}
