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

use Closure;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithRecords};
use KonradMichalik\Ttt\Factory\{RecordFactory, Records};
use LogicException;

use function assert;
use function is_subclass_of;
use function method_exists;
use function sprintf;

/**
 * RecordsHandler.
 *
 * Applies WithRecords: builds the factory from the attribute, creates the
 * records, makes them available through Records::of() and removes every
 * inserted row again afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RecordsHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithRecords;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithRecords);

        if (!is_subclass_of($attribute->factory, RecordFactory::class)) {
            throw new LogicException(sprintf('%s is not a %s.', $attribute->factory, RecordFactory::class), 1753900711);
        }

        $factory = ($attribute->factory)::new()->count($attribute->count);

        foreach ($attribute->states as $state) {
            if (!method_exists($factory, $state)) {
                throw new LogicException(sprintf('%s has no state method "%s".', $attribute->factory, $state), 1753900712);
            }

            $factory = $factory->{$state}();
        }

        if ([] !== $attribute->overrides) {
            $factory = $factory->state($attribute->overrides);
        }

        $collection = $factory->create();
        Records::register($attribute->factory, $collection);

        return static function () use ($attribute, $collection): void {
            Records::unregister($attribute->factory, $collection);
            $collection->log()->deleteAll();
        };
    }
}
