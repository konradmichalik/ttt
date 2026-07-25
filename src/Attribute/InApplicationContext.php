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
 * InApplicationContext.
 *
 * Switches the TYPO3 application context (e.g. "Development",
 * "Production/Staging") for a single test by re-initializing the Environment
 * with an identical state except for the context. Requires an already
 * initialized Environment - combine with WithEnvironment if needed.
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class InApplicationContext implements TttAttribute
{
    public function __construct(
        public string $context,
    ) {}
}
