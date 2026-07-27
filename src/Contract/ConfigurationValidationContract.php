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

namespace KonradMichalik\Ttt\Contract;

use Generator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function is_int;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function substr;

/**
 * ConfigurationValidationContract.
 *
 * Contract test case for validateConfiguration()-style APIs: describe the
 * configuration once as a declarative schema and the contract generates the
 * violation cases (missing required key, wrong type, out-of-range value).
 *
 * Schema DSL per key: "string" | "int" | "float" | "bool" | "array" | "enum",
 * numeric types optionally with ":min..max" (e.g. "float:0..1"), "enum" with
 * ":a|b|c" (e.g. "enum:top left|top right"), which additionally generates an
 * "unrecognized value" violation alongside the wrong-type case. Optional
 * keys carry a "?" suffix on the key name (e.g. "position?").
 *
 * <code>
 * final class CircleModifierValidationTest extends ConfigurationValidationContract
 * {
 *     protected function isValid(array $configuration): bool
 *     {
 *         return (new CircleModifier())->validateConfiguration($configuration);
 *     }
 *
 *     protected function validConfiguration(): array
 *     {
 *         return ['color' => '#ff0000', 'size' => 0.5];
 *     }
 *
 *     protected function schema(): array
 *     {
 *         return ['color' => 'string', 'size' => 'float:0..1'];
 *     }
 * }
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class ConfigurationValidationContract extends TestCase
{
    private const UNRECOGNIZED_ENUM_VALUE = '__ttt_unrecognized_enum_value__';

    #[Test]
    public function validConfigurationIsAccepted(): void
    {
        self::assertTrue($this->isValid($this->validConfiguration()));
    }

    #[Test]
    public function generatedViolationsAreRejected(): void
    {
        foreach ($this->generateViolations() as $description => $configuration) {
            self::assertFalse(
                $this->isValid($configuration),
                sprintf('Violation "%s" was unexpectedly accepted.', $description),
            );
        }
    }

    /**
     * @param array<string, mixed> $configuration
     */
    abstract protected function isValid(array $configuration): bool;

    /**
     * @return array<string, mixed>
     */
    abstract protected function validConfiguration(): array;

    /**
     * @return array<string, string>
     */
    abstract protected function schema(): array;

    /**
     * @return Generator<string, array<string, mixed>>
     */
    private function generateViolations(): Generator
    {
        $valid = $this->validConfiguration();

        foreach ($this->schema() as $key => $definition) {
            $optional = str_ends_with($key, '?');
            $name = $optional ? substr($key, 0, -1) : $key;
            [$type, $range, $enumValues] = self::parseDefinition($definition);

            if (!$optional) {
                $violation = $valid;
                unset($violation[$name]);
                yield sprintf('missing required key "%s"', $name) => $violation;
            }

            $violation = $valid;
            $violation[$name] = self::wrongTypeSample($type);
            yield sprintf('wrong type for "%s"', $name) => $violation;

            if (null !== $range) {
                [$min, $max] = $range;

                $violation = $valid;
                $violation[$name] = is_int($valid[$name] ?? 0) ? $min - 1 : $min - 1.0;
                yield sprintf('"%s" below minimum', $name) => $violation;

                $violation = $valid;
                $violation[$name] = is_int($valid[$name] ?? 0) ? $max + 1 : $max + 1.0;
                yield sprintf('"%s" above maximum', $name) => $violation;
            }

            if (null !== $enumValues) {
                $violation = $valid;
                $violation[$name] = self::UNRECOGNIZED_ENUM_VALUE;
                yield sprintf('unrecognized value for "%s"', $name) => $violation;
            }
        }
    }

    /**
     * @return array{string, array{float, float}|null, list<string>|null}
     */
    private static function parseDefinition(string $definition): array
    {
        if (!str_contains($definition, ':')) {
            return [$definition, null, null];
        }

        [$type, $rawConstraint] = explode(':', $definition, 2);

        if ('enum' === $type) {
            return [$type, null, explode('|', $rawConstraint)];
        }

        [$min, $max] = explode('..', $rawConstraint, 2);

        return [$type, [(float) $min, (float) $max], null];
    }

    private static function wrongTypeSample(string $type): mixed
    {
        return match ($type) {
            'string', 'enum' => 12345,
            'int', 'float' => 'not-a-number',
            'bool' => 'not-a-bool',
            'array' => 'not-an-array',
            default => new stdClass(),
        };
    }
}
