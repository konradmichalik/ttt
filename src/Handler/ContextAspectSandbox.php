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

namespace KonradMichalik\Ttt\Handler;

use Closure;
use ReflectionProperty;
use TYPO3\CMS\Core\Context\{AspectInterface, Context};

use function array_key_exists;

/**
 * ContextAspectSandbox.
 *
 * Shared aspect-sandbox approach for handlers that register a Context
 * aspect and must restore it exactly afterwards: Context::hasAspect()
 * returns true unconditionally for the six default aspects (date,
 * visibility, backend.user, frontend.user, workspace, language), so prior
 * presence is snapshotted via reflection on the protected Context::$aspects
 * array instead.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ContextAspectSandbox
{
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * @return Closure(): void Restorer reverting the aspect to its previous state
     */
    public static function apply(Context $context, string $aspectName, AspectInterface $newAspect): Closure
    {
        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        /** @var array<string, AspectInterface> $snapshot */
        $snapshot = $aspectsProperty->getValue($context);
        $existed = array_key_exists($aspectName, $snapshot);
        $previous = $snapshot[$aspectName] ?? null;

        $context->setAspect($aspectName, $newAspect);

        return static function () use ($context, $aspectName, $existed, $previous): void {
            if ($existed && null !== $previous) {
                $context->setAspect($aspectName, $previous);
            } else {
                $context->unsetAspect($aspectName);
            }
        };
    }
}
