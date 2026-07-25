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

namespace KonradMichalik\Ttt\Attribute;

/**
 * TttAttribute.
 *
 * Marker interface for all Terrarium sandbox attributes. Attributes are pure
 * DTOs - the actual sandboxing logic lives in the corresponding handler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
interface TttAttribute {}
