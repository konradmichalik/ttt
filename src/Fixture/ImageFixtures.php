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

use RuntimeException;

use function bin2hex;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefill;
use function imagepng;
use function random_bytes;
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
        $path ??= sys_get_temp_dir().'/ttt-'.bin2hex(random_bytes(16)).'.png';

        $image = imagecreatetruecolor($width, $height);

        if (false === $image) {
            throw new RuntimeException('Unable to create GD image resource.', 1752561602);
        }

        $color = imagecolorallocate($image, ...$rgb);
        imagefill($image, 0, 0, (int) $color);
        imagepng($image, $path);

        return $path;
    }
}
