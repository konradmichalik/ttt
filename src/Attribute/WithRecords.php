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
use KonradMichalik\Ttt\Factory\RecordFactory;

/**
 * WithRecords.
 *
 * Creates records through a RecordFactory before the test body runs and
 * deletes every row it inserted afterwards (records, MM rows, translations).
 * Relations stay in the imperative factory API, because attribute arguments
 * are constant expressions. Read the records inside the test with
 * Records::of(FactoryClass::class).
 *
 * Needs a database: use it in functional tests. Requires typo3/cms-core and
 * fakerphp/faker.
 *
 * <code>
 * #[WithRecords(NewsFactory::class, count: 3, states: ['published'], overrides: ['title' => 'Fixed title'])]
 * public function listShowsPublishedNews(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithRecords implements TttAttribute
{
    /**
     * @param class-string<RecordFactory> $factory
     * @param list<string>                $states    names of state methods on the factory, applied in order
     * @param array<string, mixed>        $overrides
     */
    public function __construct(
        public string $factory,
        public int $count = 1,
        public array $states = [],
        public array $overrides = [],
    ) {}
}
