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

namespace KonradMichalik\Ttt\PHPStan;

use KonradMichalik\Ttt\Attribute\{WithTca, WithTypo3ConfVars};
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\{ArrayDimFetch, Assign, Variable};
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\{Rule, RuleError, RuleErrorBuilder};

use function sprintf;

/**
 * ManualGlobalsRule.
 *
 * Reports `$GLOBALS['TYPO3_CONF_VARS']` and `$GLOBALS['TCA']` set by hand in a test.
 *
 * @implements Rule<Assign>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ManualGlobalsRule implements Rule
{
    private const ATTRIBUTES = [
        'TYPO3_CONF_VARS' => WithTypo3ConfVars::class,
        'TCA' => WithTca::class,
    ];

    public function __construct(private TestContext $context) {}

    public function getNodeType(): string
    {
        return Assign::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $key = $this->globalsKey($node->var);
        if (null === $key || !isset(self::ATTRIBUTES[$key])) {
            return [];
        }

        // Replacing the whole global is not a deep merge, so a ttt attribute would change what the test does.
        if ($node->var instanceof ArrayDimFetch && $this->isGlobals($node->var->var)) {
            return [];
        }

        $attribute = self::ATTRIBUTES[$key];
        if (!$this->context->isUnitTest($scope) || $this->context->isSandboxed($scope, $attribute)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('$GLOBALS[\'%s\'] is set by hand in a test, use #[%s] instead.', $key, substr($attribute, (int) strrpos($attribute, '\\') + 1)))
                ->identifier('ttt.manualGlobals')
                ->build(),
        ];
    }

    private function globalsKey(Expr $expr): ?string
    {
        while ($expr instanceof ArrayDimFetch) {
            if ($this->isGlobals($expr->var)) {
                return $expr->dim instanceof String_ ? $expr->dim->value : null;
            }
            $expr = $expr->var;
        }

        return null;
    }

    private function isGlobals(Expr $expr): bool
    {
        return $expr instanceof Variable && 'GLOBALS' === $expr->name;
    }
}
