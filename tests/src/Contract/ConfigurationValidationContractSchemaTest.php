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
use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;

/**
 * ConfigurationValidationContractSchemaTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ConfigurationValidationContract::class)]
final class ConfigurationValidationContractSchemaTest extends TestCase
{
    #[Test]
    public function generatesIntegerViolationsForIntegerRanges(): void
    {
        $contract = RecordingContract::create(['count' => 5], ['count' => 'int:1..10']);

        $contract->generatedViolationsAreRejected();

        self::assertContains(0, array_column($contract->seen, 'count'));
        self::assertContains(11, array_column($contract->seen, 'count'));
        self::assertNotContains(0.0, array_column($contract->seen, 'count'));
        self::assertNotContains(11.0, array_column($contract->seen, 'count'));
    }

    #[Test]
    public function generatesFloatViolationsForFloatRanges(): void
    {
        $contract = RecordingContract::create(['size' => 0.5], ['size' => 'float:0..1']);

        $contract->generatedViolationsAreRejected();

        self::assertContains(-1.0, array_column($contract->seen, 'size'));
        self::assertContains(2.0, array_column($contract->seen, 'size'));
    }

    #[Test]
    #[DataProvider('invalidDefinitions')]
    public function rejectsInvalidSchemaDefinitions(string $definition): void
    {
        $contract = RecordingContract::create(['key' => 1], ['key' => $definition]);

        $this->expectException(LogicException::class);

        $contract->generatedViolationsAreRejected();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'unknown type' => ['strng'];
        yield 'range without separator' => ['float:0'];
        yield 'constraint on a non numeric type' => ['string:0..1'];
    }
}

/**
 * RecordingContract.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RecordingContract extends ConfigurationValidationContract
{
    /** @var list<array<string, mixed>> */
    public array $seen = [];

    /** @var array<string, mixed> */
    public array $valid = [];

    /** @var array<string, string> */
    public array $schema = [];

    /**
     * @param array<string, mixed>  $valid
     * @param array<string, string> $schema
     */
    public static function create(array $valid, array $schema): self
    {
        $contract = new self('generatedViolationsAreRejected');
        $contract->valid = $valid;
        $contract->schema = $schema;

        return $contract;
    }

    protected function isValid(array $configuration): bool
    {
        $this->seen[] = $configuration;

        return false;
    }

    protected function validConfiguration(): array
    {
        return $this->valid;
    }

    protected function schema(): array
    {
        return $this->schema;
    }
}
