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
 * InLocale.
 *
 * Sets the locale (setlocale()) for the given category for the duration of
 * the test and restores the previous one afterwards. Port of JUnit
 * Pioneer's @DefaultLocale. No typo3/cms-core requirement. Repeatable, so
 * independent categories (LC_TIME, LC_MONETARY, ...) can be set separately.
 *
 * The locale is process-global state: safe under paratest (one process per
 * worker), unsafe under any runner sharing a process across tests running
 * concurrently.
 *
 * <code>
 * #[InLocale(LC_ALL, 'de_DE.UTF-8')]
 * public function formatsNumbersInGermanNotation(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class InLocale implements TttAttribute
{
    public function __construct(
        public int $category,
        public string $locale,
    ) {}
}
