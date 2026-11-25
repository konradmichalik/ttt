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
use PhpParser\Node\Expr\{ArrayDimFetch, Assign, FuncCall, StaticCall, Variable};
use PhpParser\Node\{Identifier, Name};
use PHPStan\Analyser\Scope;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * DirectGlobalStateRule.
 *
 * Reports direct changes of global state inside PHPUnit test cases and points
 * to the ttt attribute that puts the same state in place and guarantees its
 * cleanup. Only unit tests are checked: functional tests run with the
 * testing framework's own bootstrap and are skipped, as are the tests of ttt
 * itself.
 *
 * Registered through "extra.phpstan.includes" in composer.json, so it is
 * active wherever ttt and phpstan/extension-installer are installed.
 *
 * @implements Rule<Node>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class DirectGlobalStateRule implements Rule
{
    public const IDENTIFIER = 'ttt.directGlobalState';

    private const OWN_TEST_NAMESPACE = 'KonradMichalik\\Ttt\\Tests\\';

    private const GLOBALS_ATTRIBUTES = [
        'TYPO3_CONF_VARS' => 'WithTypo3ConfVars',
        'BE_USER' => 'WithBackendUser',
        'TCA' => 'WithTca',
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Assign && !$node instanceof FuncCall && !$node instanceof StaticCall) {
            return [];
        }

        if (($node instanceof FuncCall || $node instanceof StaticCall) && $node->isFirstClassCallable()) {
            return [];
        }

        if (!$this->isCheckedTest($scope)) {
            return [];
        }

        $finding = match (true) {
            $node instanceof Assign => $this->assignment($node),
            $node instanceof FuncCall => $this->functionCall($node),
            default => $this->staticCall($node, $scope),
        };

        if (null === $finding) {
            return [];
        }

        [$subject, $attribute] = $finding;

        return [
            RuleErrorBuilder::message(sprintf('Test changes global state directly: %s.', $subject))
                ->identifier(self::IDENTIFIER)
                ->tip(sprintf('Use #[%s] instead, it restores the state after the test. See docs/phpstan-rule.md of konradmichalik/ttt.', $attribute))
                ->build(),
        ];
    }

    private function isCheckedTest(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        if (null === $class || !$class->isSubclassOf(TestCase::class)) {
            return false;
        }

        return !$class->isSubclassOf(FunctionalTestCase::class)
            && !str_starts_with($class->getName(), self::OWN_TEST_NAMESPACE);
    }

    /**
     * @return array{string, string}|null
     */
    private function assignment(Assign $node): ?array
    {
        $first = null;
        $current = $node->var;

        while ($current instanceof ArrayDimFetch) {
            $first = $current;
            $current = $current->var;
        }

        if (null === $first || !$current instanceof Variable || 'GLOBALS' !== $current->name) {
            return null;
        }

        $key = $first->dim instanceof Node\Scalar\String_ ? $first->dim->value : null;

        if (null === $key) {
            return ['$GLOBALS[...] assignment', 'WithGlobal'];
        }

        return [sprintf('$GLOBALS[\'%s\'] assignment', $key), self::GLOBALS_ATTRIBUTES[$key] ?? 'WithGlobal'];
    }

    /**
     * @return array{string, string}|null
     */
    private function functionCall(FuncCall $node): ?array
    {
        if ($node->name instanceof Name && 'putenv' === $node->name->toLowerString()) {
            return ['putenv()', 'WithEnvVar'];
        }

        return null;
    }

    /**
     * @return array{string, string}|null
     */
    private function staticCall(StaticCall $node, Scope $scope): ?array
    {
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return null;
        }

        if (GeneralUtility::class !== $scope->resolveName($node->class)) {
            return null;
        }

        $method = $node->name->toString();

        if (!in_array($method, ['setSingletonInstance', 'addInstance'], true)) {
            return null;
        }

        return [
            sprintf('GeneralUtility::%s()', $method),
            'setSingletonInstance' === $method ? 'WithSingleton' : 'WithInstance',
        ];
    }
}
