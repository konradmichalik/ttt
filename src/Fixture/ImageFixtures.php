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

namespace KonradMichalik\Ttt\Fixture;

use InvalidArgumentException;
use RuntimeException;

use function bin2hex;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefill;
use function imagepng;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

/**
 * ImageFixtures.
 *
 * Creates disposable PNG test images via GD - shared replacement for
 * per-repository "create test image" traits. Requires ext-gd.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ImageFixtures
{
    private function __construct() {}

    /**
     * @param array{int, int, int} $rgb
     *
     * @return string Absolute path of the created PNG file
     */
    public static function createPng(int $width = 64, int $height = 64, array $rgb = [200, 200, 200], ?string $path = null): string
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException(sprintf('Image dimensions must be positive, got %dx%d.', $width, $height), 1752561610);
        }

        [$red, $green, $blue] = $rgb;

        if ($red < 0 || $red > 255 || $green < 0 || $green > 255 || $blue < 0 || $blue > 255) {
            throw new InvalidArgumentException(sprintf('RGB components must be within 0-255, got [%d, %d, %d].', $red, $green, $blue), 1752561611);
        }

        $path ??= sys_get_temp_dir().'/ttt-'.bin2hex(random_bytes(16)).'.png';

        $image = imagecreatetruecolor($width, $height);

        if (false === $image) {
            throw new RuntimeException('Unable to create GD image resource.', 1752561602);
        }

        $color = imagecolorallocate($image, $red, $green, $blue);
        imagefill($image, 0, 0, (int) $color);
        imagepng($image, $path);

        return $path;
    }
}
