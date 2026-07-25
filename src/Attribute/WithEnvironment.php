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
 * WithEnvironment.
 *
 * Bootstraps TYPO3's Environment for a single test (or test class) via
 * Environment::initialize(). By default a temporary project directory
 * (including public/, var/ and config/) is created and deleted afterwards.
 * If the Environment was initialized before, the previous state is restored;
 * if it was NOT initialized before, it stays initialized with neutral values
 * (un-initializing typed static properties is not possible in PHP).
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class WithEnvironment implements TttAttribute
{
    public function __construct(
        public string $context = 'Testing',
        public bool $temporaryProjectPath = true,
        public ?string $projectPath = null,
        public bool $cli = true,
        public bool $composerMode = true,
    ) {}
}
