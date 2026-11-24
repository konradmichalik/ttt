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

namespace KonradMichalik\Ttt\Tests\Factory\Support;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool, DriverMiddlewareService};
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function sprintf;

/**
 * SqliteTestCase.
 *
 * Gives factory tests a real in-memory database and a small TCA, so
 * persistence, relations and cleanup run against actual rows without a
 * functional test setup.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class SqliteTestCase extends TestCase
{
    /** @var array<string, mixed> */
    private static array $previousGlobals = [];

    /** @var array<class-string, SingletonInterface> */
    private static array $previousSingletons = [];

    public static function setUpBeforeClass(): void
    {
        self::$previousSingletons = GeneralUtility::getSingletonInstances();
        self::$previousGlobals = [
            'TCA' => $GLOBALS['TCA'] ?? null,
            'DB' => $GLOBALS['TYPO3_CONF_VARS']['DB'] ?? null,
        ];
    }

    /**
     * Not in tearDown(): Terrarium restores attributes on Test\Finished, after
     * tearDown(), and #[WithRecords] still needs the database then.
     */
    public static function tearDownAfterClass(): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->resetConnections();
        GeneralUtility::resetSingletonInstances(self::$previousSingletons);

        if (null === self::$previousGlobals['TCA']) {
            unset($GLOBALS['TCA']);
        } else {
            $GLOBALS['TCA'] = self::$previousGlobals['TCA'];
        }

        if (null === self::$previousGlobals['DB']) {
            unset($GLOBALS['TYPO3_CONF_VARS']['DB']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['DB'] = self::$previousGlobals['DB'];
        }

        if ([] === ($GLOBALS['TYPO3_CONF_VARS'] ?? null)) {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        }
    }

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = ['driver' => 'pdo_sqlite', 'memory' => true];
        $GLOBALS['TCA'] = self::tca();

        GeneralUtility::makeInstance(ConnectionPool::class)->resetConnections();
        GeneralUtility::addInstance(DriverMiddlewareService::class, new DriverMiddlewareService(new DependencyOrderingService()));

        $connection = $this->connection();

        foreach (self::schema() as $statement) {
            $connection->executeStatement($statement);
        }
    }

    protected function connection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
    }

    protected function countRows(string $table): int
    {
        return (int) $this->connection()->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchRow(string $table, int $uid): array
    {
        return $this->connection()->fetchAssociative(sprintf('SELECT * FROM %s WHERE uid = ?', $table), [$uid]) ?: [];
    }

    /**
     * @return list<string>
     */
    private static function schema(): array
    {
        return [
            'CREATE TABLE pages (uid INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER DEFAULT 0, title TEXT, crdate INTEGER DEFAULT 0, tstamp INTEGER DEFAULT 0, hidden INTEGER DEFAULT 0, sys_language_uid INTEGER DEFAULT 0, l10n_parent INTEGER DEFAULT 0)',
            'CREATE TABLE tx_news (uid INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER DEFAULT 0, title TEXT, teaser TEXT, datetime INTEGER DEFAULT 0, hidden INTEGER DEFAULT 0, type TEXT, priority INTEGER DEFAULT 0, categories INTEGER DEFAULT 0, comments INTEGER DEFAULT 0, related TEXT, mixed TEXT, discussion INTEGER DEFAULT 0, main_category INTEGER DEFAULT 0, topics INTEGER DEFAULT 0, attachments INTEGER DEFAULT 0, crdate INTEGER DEFAULT 0, tstamp INTEGER DEFAULT 0, sys_language_uid INTEGER DEFAULT 0, l10n_parent INTEGER DEFAULT 0)',
            'CREATE TABLE tx_news_comment (uid INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER DEFAULT 0, news INTEGER DEFAULT 0, message TEXT, sorting INTEGER DEFAULT 0, parenttable TEXT)',
            'CREATE TABLE sys_category (uid INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER DEFAULT 0, title TEXT)',
            'CREATE TABLE sys_category_record_mm (uid_local INTEGER, uid_foreign INTEGER, tablenames TEXT, fieldname TEXT, sorting INTEGER DEFAULT 0, sorting_foreign INTEGER DEFAULT 0)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tca(): array
    {
        $translatable = ['crdate' => 'crdate', 'tstamp' => 'tstamp', 'languageField' => 'sys_language_uid', 'transOrigPointerField' => 'l10n_parent'];

        return [
            'pages' => [
                'ctrl' => $translatable,
                'columns' => ['title' => ['config' => ['type' => 'input', 'max' => 255]]],
            ],
            'tx_news' => [
                'ctrl' => $translatable,
                'columns' => [
                    'title' => ['config' => ['type' => 'input', 'max' => 255]],
                    'teaser' => ['config' => ['type' => 'text']],
                    'datetime' => ['config' => ['type' => 'datetime']],
                    'hidden' => ['config' => ['type' => 'check']],
                    'type' => ['config' => ['type' => 'select', 'items' => [['label' => 'Default', 'value' => 'default'], ['label' => 'Other', 'value' => 'other']]]],
                    'priority' => ['config' => ['type' => 'number', 'range' => ['lower' => 1, 'upper' => 5]]],
                    'categories' => ['config' => ['type' => 'category', 'MM' => 'sys_category_record_mm', 'foreign_table' => 'sys_category']],
                    'mixed' => ['config' => ['type' => 'group', 'allowed' => 'tx_news_comment,sys_category']],
                    'comments' => ['config' => ['type' => 'inline', 'foreign_table' => 'tx_news_comment', 'foreign_field' => 'news', 'foreign_sortby' => 'sorting']],
                    'attachments' => ['config' => ['type' => 'inline', 'foreign_table' => 'tx_news_comment', 'foreign_field' => 'news', 'foreign_table_field' => 'parenttable']],
                    'discussion' => ['config' => ['type' => 'inline', 'foreign_table' => 'tx_news_comment', 'foreign_field' => 'news', 'foreign_match_fields' => ['parenttable' => 'discussion']]],
                    'main_category' => ['config' => ['type' => 'category', 'relationship' => 'oneToOne', 'foreign_table' => 'sys_category']],
                    'related' => ['config' => ['type' => 'select', 'foreign_table' => 'tx_news_comment']],
                    'topics' => ['config' => ['type' => 'select', 'foreign_table' => 'sys_category', 'MM' => 'sys_category_record_mm', 'MM_match_fields' => ['tablenames' => 'tx_news', 'fieldname' => 'topics', 'ignored' => []]]],
                ],
            ],
            'tx_news_comment' => [
                'ctrl' => [],
                'columns' => ['message' => ['config' => ['type' => 'text']]],
            ],
            'sys_category' => [
                'ctrl' => [],
                'columns' => ['title' => ['config' => ['type' => 'input']]],
            ],
        ];
    }
}
