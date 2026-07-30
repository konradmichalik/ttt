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

namespace TYPO3\TestingFramework\Core\Functional;

use PHPUnit\Framework\TestCase;

/**
 * FunctionalTestCase.
 *
 * Minimal stand-in for typo3/testing-framework's FunctionalTestCase.
 *
 * ttt never depends on typo3/testing-framework (see FunctionalTestCaseGuard);
 * this stub exists solely so the guard's "framework present" branch can be
 * exercised by this repository's own test suite.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class FunctionalTestCase extends TestCase {}
