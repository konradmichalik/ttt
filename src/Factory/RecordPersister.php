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

use RuntimeException;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function is_array;
use function sprintf;

/**
 * RecordPersister.
 *
 * Writes rows with plain parameterized statements through the table's
 * connection: no restrictions, no schema lookups and no DataHandler, so
 * the result matches what a CSV fixture import produces.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RecordPersister
{
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * Inserts a row and returns its uid. Rows of tables without an
     * auto-increment uid (MM tables) go through insertWithoutUid(), because
     * MySQL reports no insert id for them.
     *
     * @param array<string, mixed> $row
     */
    public static function insert(string $table, array $row): int
    {
        self::insertWithoutUid($table, $row);

        return (int) self::connection($table)->lastInsertId();
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function insertWithoutUid(string $table, array $row): void
    {
        $connection = self::connection($table);
        $columns = implode(', ', array_map($connection->quoteIdentifier(...), array_keys($row)));
        $placeholders = implode(', ', array_map(static fn (): string => '?', $row));

        $connection->executeStatement(
            sprintf('INSERT INTO %s (%s) VALUES (%s)', $connection->quoteIdentifier($table), $columns, $placeholders),
            array_values($row),
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function update(string $table, int $uid, array $values): void
    {
        $connection = self::connection($table);
        $assignments = implode(', ', array_map(static fn (string $column): string => $connection->quoteIdentifier($column).' = ?', array_keys($values)));

        $connection->executeStatement(
            sprintf('UPDATE %s SET %s WHERE %s = ?', $connection->quoteIdentifier($table), $assignments, $connection->quoteIdentifier('uid')),
            [...array_values($values), $uid],
        );
    }

    /**
     * @param array<string, int|string> $where
     */
    public static function delete(string $table, array $where): void
    {
        $connection = self::connection($table);
        $conditions = implode(' AND ', array_map(static fn (string $column): string => $connection->quoteIdentifier($column).' = ?', array_keys($where)));

        $connection->executeStatement(
            sprintf('DELETE FROM %s WHERE %s', $connection->quoteIdentifier($table), $conditions),
            array_values($where),
        );
    }

    private static function connection(string $table): Connection
    {
        $connections = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] ?? null;

        if (!is_array($connections) || [] === $connections) {
            throw new RuntimeException('Record factories write to the real test database, but no database connection is configured. Use them in a functional test; a unit test has no database.', 1753900701);
        }

        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }
}
