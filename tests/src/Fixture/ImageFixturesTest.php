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

namespace KonradMichalik\Ttt\Tests\Fixture;

use InvalidArgumentException;
use KonradMichalik\Ttt\Fixture\ImageFixtures;
use PHPUnit\Framework\Attributes\{CoversClass, RequiresPhpExtension, Test};
use PHPUnit\Framework\TestCase;

use function array_slice;
use function getimagesize;
use function unlink;

/**
 * ImageFixturesTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ImageFixtures::class)]
#[RequiresPhpExtension('gd')]
final class ImageFixturesTest extends TestCase
{
    #[Test]
    public function createsPngWithRequestedDimensions(): void
    {
        $path = ImageFixtures::createPng(32, 16);

        self::assertFileExists($path);
        self::assertSame([32, 16], array_slice((array) getimagesize($path), 0, 2));

        unlink($path);
    }

    #[Test]
    public function rejectsNonPositiveDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1752561610);

        ImageFixtures::createPng(0, 10);
    }

    #[Test]
    public function rejectsRgbComponentsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1752561611);

        ImageFixtures::createPng(8, 8, [300, 0, 0]);
    }
}
