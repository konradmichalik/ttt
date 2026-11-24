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

/**
 * Record.
 *
 * A row created by a RecordFactory: table, uid, the inserted data and the
 * translated overlay records, if any.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class Record
{
    /**
     * @param array<string, mixed> $data
     * @param list<Record>         $translations
     */
    public function __construct(
        public string $table,
        public int $uid,
        public array $data,
        public array $translations = [],
    ) {}

    public function getUid(): int
    {
        return $this->uid;
    }

    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }
}
