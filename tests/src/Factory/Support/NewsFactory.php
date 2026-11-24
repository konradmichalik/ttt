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

use Faker\Generator;
use KonradMichalik\Ttt\Factory\RecordFactory;

/**
 * NewsFactory.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class NewsFactory extends RecordFactory
{
    protected string $table = 'tx_news';

    public function published(): static
    {
        return $this->state(['hidden' => 0, 'type' => 'other']);
    }

    protected function definition(Generator $faker): array
    {
        return ['teaser' => 'defined teaser'];
    }
}
