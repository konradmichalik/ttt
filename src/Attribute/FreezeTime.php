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

namespace KonradMichalik\Ttt\Attribute;

use Attribute;

/**
 * FreezeTime.
 *
 * Pins TYPO3's date aspect (Context "date") and the legacy execution time
 * globals (EXEC_TIME, SIM_EXEC_TIME, ACCESS_TIME, SIM_ACCESS_TIME) to a fixed
 * point in time for deterministic tests. Accepts any string understood by
 * DateTimeImmutable::__construct().
 *
 * Scope is deliberately narrow: it does not affect `new DateTimeImmutable()`,
 * `time()` or `date()` calls in the code under test, since those read the
 * system clock directly rather than TYPO3's time abstractions.
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class FreezeTime implements TttAttribute
{
    public function __construct(
        public string $dateTime,
    ) {}
}
