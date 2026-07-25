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

namespace KonradMichalik\Ttt\Tests\Contract;

use KonradMichalik\Ttt\Contract\ConfigurationValidationContract;

use function is_array;
use function is_float;
use function is_int;
use function is_string;

/**
 * ConfigurationValidationContractTest.
 *
 * Exercises the contract against a small hand-written validator covering
 * required keys, optional keys, type checks and numeric ranges.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ConfigurationValidationContractTest extends ConfigurationValidationContract
{
    protected function isValid(array $configuration): bool
    {
        if (!is_string($configuration['color'] ?? null)) {
            return false;
        }

        $size = $configuration['size'] ?? null;

        if (!is_float($size) && !is_int($size)) {
            return false;
        }

        if ($size < 0 || $size > 1) {
            return false;
        }

        if (isset($configuration['options']) && !is_array($configuration['options'])) {
            return false;
        }

        return true;
    }

    protected function validConfiguration(): array
    {
        return ['color' => '#ff0000', 'size' => 0.5, 'options' => []];
    }

    protected function schema(): array
    {
        return [
            'color' => 'string',
            'size' => 'float:0..1',
            'options?' => 'array',
        ];
    }
}
