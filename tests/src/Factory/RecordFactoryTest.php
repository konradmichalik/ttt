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

namespace KonradMichalik\Ttt\Tests\Factory;

use KonradMichalik\Ttt\Factory\{CreationLog, Record, RecordCollection, RecordFactory, RecordPersister, TcaDefaults};
use KonradMichalik\Ttt\Tests\Factory\Support\{CategoryFactory, CommentFactory, NewsFactory, NoTableFactory, PageFactory, SqliteTestCase};
use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use RuntimeException;

/**
 * RecordFactoryTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(RecordFactory::class)]
#[CoversClass(Record::class)]
#[CoversClass(RecordCollection::class)]
#[CoversClass(RecordPersister::class)]
#[CoversClass(CreationLog::class)]
#[CoversClass(TcaDefaults::class)]
final class RecordFactoryTest extends SqliteTestCase
{
    #[Test]
    public function createsAValidRecordFromTcaAlone(): void
    {
        $record = PageFactory::new()->create()->first();

        $row = $this->fetchRow('pages', $record->getUid());

        self::assertNotSame('', $row['title']);
        self::assertSame(0, $row['hidden']);
        self::assertGreaterThan(0, $row['crdate']);
        self::assertSame($record->get('title'), $row['title']);
    }

    #[Test]
    public function derivesDefaultsFromTheColumnTypes(): void
    {
        $row = NewsFactory::new()->create()->first()->data;

        self::assertSame('default', $row['type']);
        self::assertGreaterThanOrEqual(1, $row['priority']);
        self::assertLessThanOrEqual(5, $row['priority']);
        self::assertIsInt($row['datetime']);
        self::assertSame(0, $row['hidden']);
        self::assertNotSame('', $row['teaser']);
    }

    #[Test]
    public function definitionAndStatesOverrideTheDefaultsInThatOrder(): void
    {
        $row = NewsFactory::new()->published()->state(['title' => 'Fixed'])->create()->first()->data;

        self::assertSame('defined teaser', $row['teaser']);
        self::assertSame('other', $row['type']);
        self::assertSame('Fixed', $row['title']);
    }

    #[Test]
    public function createsTheRequestedNumberOfRecords(): void
    {
        $collection = NewsFactory::new()->count(3)->create();

        self::assertCount(3, $collection);
        self::assertSame(3, $this->countRows('tx_news'));
        self::assertCount(3, array_unique($collection->uids()));
        self::assertSame($collection->first(), $collection->all()[0]);
    }

    #[Test]
    public function makeBuildsRowsWithoutPersistingThem(): void
    {
        $rows = NewsFactory::new()->count(2)->make();

        self::assertCount(2, $rows);
        self::assertSame(0, $this->countRows('tx_news'));
    }

    #[Test]
    public function theSameTestProducesTheSameData(): void
    {
        self::assertSame(NewsFactory::new()->make(), NewsFactory::new()->make());
    }

    #[Test]
    public function factoriesAreImmutable(): void
    {
        $factory = NewsFactory::new();
        $factory->count(5)->state(['title' => 'x']);

        self::assertCount(1, $factory->make());
        self::assertNotSame('x', $factory->make()[0]['title']);
    }

    #[Test]
    public function forCreatesTheParentFirstAndStoresItsUid(): void
    {
        $news = NewsFactory::new()->for(PageFactory::new(), 'pid')->create()->first();

        self::assertSame(1, $this->countRows('pages'));
        self::assertSame($news->get('pid'), $this->fetchRow('pages', (int) $news->get('pid'))['uid']);
        self::assertSame((int) $news->get('pid'), (int) $this->fetchRow('tx_news', $news->uid)['pid']);
    }

    #[Test]
    public function hasWiresCategoriesThroughTheMmTable(): void
    {
        $news = NewsFactory::new()->has(CategoryFactory::new()->count(2), 'categories')->create()->first();

        self::assertSame(2, $this->countRows('sys_category'));
        self::assertSame(2, $this->countRows('sys_category_record_mm'));
        self::assertSame(2, $this->fetchRow('tx_news', $news->uid)['categories']);
        self::assertSame(2, $news->get('categories'));

        $mm = $this->connection()->fetchAllAssociative('SELECT * FROM sys_category_record_mm ORDER BY sorting_foreign');

        self::assertSame($news->uid, $mm[0]['uid_foreign']);
        self::assertSame('tx_news', $mm[0]['tablenames']);
        self::assertSame('categories', $mm[0]['fieldname']);
        self::assertSame([1, 2], [$mm[0]['sorting_foreign'], $mm[1]['sorting_foreign']]);
    }

    #[Test]
    public function hasWiresInlineChildrenThroughTheForeignField(): void
    {
        $news = NewsFactory::new()->has(CommentFactory::new()->count(2), 'comments')->create()->first();

        $comments = $this->connection()->fetchAllAssociative('SELECT * FROM tx_news_comment ORDER BY sorting');

        self::assertCount(2, $comments);
        self::assertSame([$news->uid, $news->uid], [$comments[0]['news'], $comments[1]['news']]);
        self::assertSame([1, 2], [$comments[0]['sorting'], $comments[1]['sorting']]);
        self::assertSame(2, $this->fetchRow('tx_news', $news->uid)['comments']);
    }

    #[Test]
    public function hasStoresTheParentTableForPolymorphicInlineRelations(): void
    {
        NewsFactory::new()->has(CommentFactory::new(), 'attachments')->create();

        self::assertSame('tx_news', $this->connection()->fetchOne('SELECT parenttable FROM tx_news_comment'));
    }

    #[Test]
    public function hasAppliesForeignMatchFieldsToInlineChildren(): void
    {
        NewsFactory::new()->has(CommentFactory::new(), 'discussion')->create();

        self::assertSame('discussion', $this->connection()->fetchOne('SELECT parenttable FROM tx_news_comment'));
    }

    #[Test]
    public function hasStoresTheUidForCategoriesWithoutAnMmTable(): void
    {
        $news = NewsFactory::new()->has(CategoryFactory::new(), 'main_category')->create()->first();

        self::assertSame('1', (string) $this->fetchRow('tx_news', $news->uid)['main_category']);
    }

    #[Test]
    public function hasStoresACommaSeparatedUidListForPlainSelectRelations(): void
    {
        $news = NewsFactory::new()->has(CommentFactory::new()->count(2), 'related')->create()->first();

        self::assertSame(implode(',', [1, 2]), $this->fetchRow('tx_news', $news->uid)['related']);
    }

    #[Test]
    public function hasWritesMatchFieldsIntoTheMmRows(): void
    {
        $news = NewsFactory::new()->has(CategoryFactory::new(), 'topics')->create()->first();

        $mm = $this->connection()->fetchAssociative('SELECT * FROM sys_category_record_mm') ?: [];

        self::assertSame($news->uid, $mm['uid_local']);
        self::assertSame('tx_news', $mm['tablenames']);
        self::assertSame('topics', $mm['fieldname']);
    }

    #[Test]
    public function categoriesKeepTheirOrderInSortingForeign(): void
    {
        NewsFactory::new()->has(CategoryFactory::new()->count(2), 'categories')->create();

        $mm = $this->connection()->fetchAllAssociative('SELECT sorting_foreign FROM sys_category_record_mm ORDER BY sorting_foreign');

        self::assertSame([1, 2], array_column($mm, 'sorting_foreign'));
    }

    #[Test]
    public function aFailingRelationLeavesNoRowsBehind(): void
    {
        try {
            NewsFactory::new()->for(PageFactory::new(), 'pid')->has(CommentFactory::new(), 'missing')->create();
            self::fail('Expected LogicException was not thrown.');
        } catch (LogicException) {
        }

        self::assertSame(0, $this->countRows('pages'));
        self::assertSame(0, $this->countRows('tx_news'));
        self::assertSame(0, $this->countRows('tx_news_comment'));
    }

    #[Test]
    public function collectionsCanBeIterated(): void
    {
        $uids = [];

        foreach (NewsFactory::new()->count(2)->create() as $record) {
            $uids[] = $record->uid;
        }

        self::assertSame([1, 2], $uids);
    }

    #[Test]
    public function translatedCreatesAnOverlayRecord(): void
    {
        $news = NewsFactory::new()->translated(1, ['title' => 'Übersetzt'])->create()->first();

        self::assertCount(1, $news->translations);

        $overlay = $this->fetchRow('tx_news', $news->translations[0]->uid);

        self::assertSame(1, $overlay['sys_language_uid']);
        self::assertSame($news->uid, $overlay['l10n_parent']);
        self::assertSame('Übersetzt', $overlay['title']);
        self::assertSame(2, $this->countRows('tx_news'));
    }

    #[Test]
    public function deleteAllRemovesEveryInsertedRow(): void
    {
        $collection = NewsFactory::new()
            ->for(PageFactory::new(), 'pid')
            ->has(CategoryFactory::new()->count(2), 'categories')
            ->has(CommentFactory::new(), 'comments')
            ->translated(1)
            ->create();

        $collection->log()->deleteAll();

        foreach (['pages', 'tx_news', 'sys_category', 'sys_category_record_mm', 'tx_news_comment'] as $table) {
            self::assertSame(0, $this->countRows($table), $table);
        }
    }

    #[Test]
    public function firstFailsOnAnEmptyCollection(): void
    {
        $this->expectException(LogicException::class);

        NewsFactory::new()->count(0)->create()->first();
    }

    #[Test]
    public function failsWithoutATableProperty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('$table');

        NoTableFactory::new()->make();
    }

    #[Test]
    public function failsForATableWithoutTca(): void
    {
        unset($GLOBALS['TCA']['pages']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No TCA found for table "pages"');

        PageFactory::new()->make();
    }

    #[Test]
    public function failsWhenTheChildFactoryDoesNotMatchTheForeignTable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('points to "sys_category"');

        NewsFactory::new()->has(CommentFactory::new(), 'categories')->create();
    }

    #[Test]
    public function failsForAnUnknownRelationField(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no TCA configuration');

        NewsFactory::new()->has(CommentFactory::new(), 'missing')->create();
    }

    #[Test]
    public function failsForAFieldThatCannotHoldChildren(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot hold child records');

        NewsFactory::new()->has(CommentFactory::new(), 'title')->create();
    }

    #[Test]
    public function failsToTranslateATableWithoutLanguageFields(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('not translatable');

        CategoryFactory::new()->translated(1)->create();
    }

    #[Test]
    public function failsWithAnActionableMessageWithoutADatabase(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no database connection is configured');

        PageFactory::new()->create();
    }
}
