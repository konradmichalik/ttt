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
 * InTimeZone.
 *
 * Sets the default timezone (date_default_timezone_set()) for the duration
 * of the test and restores the previous one afterwards. Port of JUnit
 * Pioneer's @DefaultTimeZone. No typo3/cms-core requirement.
 *
 * The default timezone is process-global state: safe under paratest (one
 * process per worker), unsafe under any runner sharing a process across
 * tests running concurrently.
 *
 * <code>
 * #[InTimeZone('Europe/Berlin')]
 * public function formatsDatesInTheConfiguredTimeZone(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class InTimeZone implements TttAttribute
{
    public function __construct(
        public string $timeZone,
    ) {}
}
