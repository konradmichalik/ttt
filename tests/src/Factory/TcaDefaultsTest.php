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

use Faker\{Factory, Generator};
use KonradMichalik\Ttt\Attribute\WithTca;
use KonradMichalik\Ttt\Factory\TcaDefaults;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * TcaDefaultsTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(TcaDefaults::class)]
final class TcaDefaultsTest extends TestCase
{
    #[Test]
    #[WithTca('tx_ttt_defaults', ['columns' => [
        'limited' => ['config' => ['type' => 'input', 'max' => 5]],
        'counter' => ['config' => ['type' => 'input', 'eval' => 'int']],
        'mail' => ['config' => ['type' => 'email']],
        'url' => ['config' => ['type' => 'link']],
        'path' => ['config' => ['type' => 'slug']],
        'price' => ['config' => ['type' => 'number', 'format' => 'decimal', 'range' => ['lower' => 10, 'upper' => 20]]],
        'plain' => ['config' => ['type' => 'number']],
        'exact' => ['config' => ['type' => 'number', 'format' => 'decimal', 'range' => ['lower' => 20, 'upper' => 20]]],
        'fraction' => ['config' => ['type' => 'number', 'format' => 'decimal', 'range' => ['lower' => 0.5, 'upper' => 0.75]]],
        'day' => ['config' => ['type' => 'datetime', 'dbType' => 'date']],
        'moment' => ['config' => ['type' => 'datetime', 'dbType' => 'datetime']],
        'clock' => ['config' => ['type' => 'datetime', 'dbType' => 'time']],
        'mode' => ['config' => ['type' => 'radio', 'items' => [['label' => 'A', 'value' => 3]]]],
        'legacy' => ['config' => ['type' => 'select', 'items' => [['Label', 'first']]]],
        'related' => ['config' => ['type' => 'select', 'foreign_table' => 'pages', 'items' => [['label' => 'x', 'value' => 1]]]],
        'flag' => ['config' => ['type' => 'check', 'default' => 1]],
        'label' => ['config' => ['type' => 'input', 'default' => 'fixed']],
        'count' => ['config' => ['type' => 'input', 'default' => true]],
        'weight' => ['config' => ['type' => 'input', 'default' => 1.5]],
        'files' => ['config' => ['type' => 'file']],
    ], 'ctrl' => ['crdate' => 'created']])]
    public function derivesAValueForEveryHandledColumnType(): void
    {
        $row = TcaDefaults::for('tx_ttt_defaults', self::seededFaker());

        self::assertLessThanOrEqual(5, strlen($row['limited']));
        self::assertIsInt($row['counter']);
        self::assertStringContainsString('@', $row['mail']);
        self::assertStringStartsWith('http', $row['url']);
        self::assertStringStartsWith('/', $row['path']);
        self::assertIsFloat($row['price']);
        self::assertGreaterThanOrEqual(10, $row['price']);
        self::assertLessThanOrEqual(20, $row['price']);
        self::assertSame(20.0, $row['exact']);
        self::assertGreaterThanOrEqual(0.5, $row['fraction']);
        self::assertLessThanOrEqual(0.75, $row['fraction']);
        self::assertIsInt($row['plain']);
        self::assertSame($row, TcaDefaults::for('tx_ttt_defaults', self::seededFaker()), 'the same seed is not reproducible');
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $row['day']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['moment']);
        self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $row['clock']);
        self::assertSame(3, $row['mode']);
        self::assertSame('first', $row['legacy']);
        self::assertArrayNotHasKey('related', $row);
        self::assertSame(1, $row['flag']);
        self::assertSame('fixed', $row['label']);
        self::assertSame(1, $row['count']);
        self::assertSame(1.5, $row['weight']);
        self::assertArrayNotHasKey('files', $row);
        self::assertArrayHasKey('created', $row);
        self::assertSame(0, $row['pid']);
    }

    private static function seededFaker(): Generator
    {
        $faker = Factory::create();
        $faker->seed(42);

        return $faker;
    }
}
