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

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\{Rule, RuleError, RuleErrorBuilder};

/**
 * ManualEnvVarRule.
 *
 * Reports `putenv()` in a test.
 *
 * @implements Rule<FuncCall>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ManualEnvVarRule implements Rule
{
    public function __construct(private TestContext $context) {}

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name || 'putenv' !== $node->name->toLowerString()) {
            return [];
        }

        if (!$this->context->isUnitTest($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Environment variable set or cleared with putenv() in a test, use #[WithEnvVar] instead.')
                ->identifier('ttt.manualEnvVar')
                ->build(),
        ];
    }
}
