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

use function array_reverse;

/**
 * CreationLog.
 *
 * Remembers every row a factory run inserted (records and MM rows), so that
 * all of them can be removed again.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class CreationLog
{
    /** @var list<array{string, int}> */
    private array $records = [];

    /** @var list<array{string, array<string, int|string>}> */
    private array $mmRows = [];

    public function recordInserted(string $table, int $uid): void
    {
        $this->records[] = [$table, $uid];
    }

    /**
     * @param array<string, int|string> $row
     */
    public function mmRowInserted(string $table, array $row): void
    {
        $this->mmRows[] = [$table, $row];
    }

    public function deleteAll(): void
    {
        foreach (array_reverse($this->mmRows) as [$table, $row]) {
            RecordPersister::delete($table, $row);
        }

        foreach (array_reverse($this->records) as [$table, $uid]) {
            RecordPersister::delete($table, ['uid' => $uid]);
        }

        $this->records = [];
        $this->mmRows = [];
    }
}
