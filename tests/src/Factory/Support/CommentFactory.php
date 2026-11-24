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

use KonradMichalik\Ttt\Factory\RecordFactory;

/**
 * CommentFactory.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class CommentFactory extends RecordFactory
{
    protected string $table = 'tx_news_comment';
}
