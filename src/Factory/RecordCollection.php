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

namespace KonradMichalik\Ttt\Factory;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use Traversable;

use function array_map;
use function count;

/**
 * RecordCollection.
 *
 * The records created by one factory run, in creation order.
 *
 * @implements IteratorAggregate<int, Record>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class RecordCollection implements Countable, IteratorAggregate
{
    /**
     * @param list<Record> $records
     */
    public function __construct(
        private array $records,
        private CreationLog $log,
    ) {}

    public function first(): Record
    {
        return $this->records[0] ?? throw new LogicException('The record collection is empty.', 1753900702);
    }

    /**
     * @return list<Record>
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * @return list<int>
     */
    public function uids(): array
    {
        return array_map(static fn (Record $record): int => $record->uid, $this->records);
    }

    public function log(): CreationLog
    {
        return $this->log;
    }

    public function count(): int
    {
        return count($this->records);
    }

    /**
     * @return Traversable<int, Record>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }
}
