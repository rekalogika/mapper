<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/mapper package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\DateTimeTestObjectInterface;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDatePoint;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDateTime;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDateTimeCollection;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDateTimeCollectionDto;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDateTimeImmutable;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithDateTimeInterface;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithFloat;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithFloatYYYYMMDD;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithInt;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithIntYYYYMMDD;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithString;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithStringDDMMYYYY;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithStringDDMMYYYYWithTimeZone;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithStringWithFormat;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithStringWithTimeZone;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\ObjectWithStringWithTimeZoneAndFormat;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\OldObjectWithDateTime;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\OldObjectWithDateTimeDto;
use Rekalogika\Mapper\Tests\Fixtures\DateTime\OldObjectWithDateTimeWithTimeZoneDto;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;

class DateTimeMappingTest extends FrameworkTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        Clock::set(new MockClock('2025-01-01 00:00:00', 'UTC'));

        parent::setUp();
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @dataProvider dateTimeProvider
     */
    public function testDateTime(string $sourceClass, string $targetClass): void
    {
        /** @psalm-suppress MixedMethodCall */
        $source = new $sourceClass('2021-01-01 00:00:00');
        $target = $this->mapper->map($source, $targetClass);

        $this->assertInstanceOf($targetClass, $target);
    }

    /**
     * @return iterable<string,array{0:class-string<\DateTimeInterface>,1:class-string<\DateTimeInterface>}>
     */
    public static function dateTimeProvider(): iterable
    {
        $types = [
            \DateTimeInterface::class,
            \DateTime::class,
            \DateTimeImmutable::class,
            DatePoint::class,
        ];

        foreach ($types as $sourceClass) {
            foreach ($types as $targetClass) {
                if ($sourceClass === \DateTimeInterface::class) {
                    continue;
                }

                yield \sprintf("%s to %s", $sourceClass, $targetClass) => [$sourceClass, $targetClass];
            }
        }
    }

    public function testObjectWithDateTime(): void
    {
        $source = new OldObjectWithDateTime();
        $target = $this->mapper->map($source, OldObjectWithDateTimeDto::class);

        $this->assertInstanceOf(OldObjectWithDateTimeDto::class, $target);

        $this->assertInstanceOf(\DateTimeInterface::class, $target->dateTimeInterface);
        $this->assertInstanceOf(\DateTimeImmutable::class, $target->dateTimeImmutable);
        $this->assertInstanceOf(\DateTime::class, $target->dateTime);
        $this->assertInstanceOf(DatePoint::class, $target->datePoint);

        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTimeInterface->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTimeImmutable->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTime->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->datePoint->format('Y-m-d H:i:s e'));
    }

    public function testObjectWithDateTimeWithTargetHavingExistingValues(): void
    {
        $source = new OldObjectWithDateTime();
        $target = OldObjectWithDateTimeDto::getInitialized();
        $target = $this->mapper->map($source, $target);

        $this->assertInstanceOf(OldObjectWithDateTimeDto::class, $target);

        $this->assertInstanceOf(\DateTimeInterface::class, $target->dateTimeInterface);
        $this->assertInstanceOf(\DateTimeImmutable::class, $target->dateTimeImmutable);
        $this->assertInstanceOf(\DateTime::class, $target->dateTime);
        $this->assertInstanceOf(DatePoint::class, $target->datePoint);

        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTimeInterface->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTimeImmutable->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->dateTime->format('Y-m-d H:i:s e'));
        $this->assertEquals('2024-01-01 00:00:00 UTC', $target->datePoint->format('Y-m-d H:i:s e'));
    }

    public function testTimeZoneConversion(): void
    {
        $source = new OldObjectWithDateTime();
        $target = $this->mapper->map($source, OldObjectWithDateTimeWithTimeZoneDto::class);

        $this->assertInstanceOf(OldObjectWithDateTimeWithTimeZoneDto::class, $target);

        $this->assertInstanceOf(\DateTimeInterface::class, $target->dateTimeInterface);
        $this->assertInstanceOf(\DateTimeImmutable::class, $target->dateTimeImmutable);
        $this->assertInstanceOf(\DateTime::class, $target->dateTime);
        $this->assertInstanceOf(DatePoint::class, $target->datePoint);

        $this->assertEquals(
            '2024-01-01 07:00:00 Asia/Jakarta',
            $target->dateTimeInterface->format('Y-m-d H:i:s e'),
        );
        $this->assertEquals(
            '2024-01-01 07:00:00 Asia/Jakarta',
            $target->dateTimeImmutable->format('Y-m-d H:i:s e'),
        );
        $this->assertEquals(
            '2024-01-01 07:00:00 Asia/Jakarta',
            $target->dateTime->format('Y-m-d H:i:s e'),
        );
        $this->assertEquals(
            '2024-01-01 07:00:00 Asia/Jakarta',
            $target->datePoint->format('Y-m-d H:i:s e'),
        );
    }

    public function testDateTimeCollectionAttribute(): void
    {
        $source = new ObjectWithDateTimeCollection();
        $target = $this->mapper->map($source, ObjectWithDateTimeCollectionDto::class);
        $target->datetimes->count();

        $this->assertInstanceOf(ObjectWithDateTimeCollectionDto::class, $target);
        $this->assertCount(3, $target->datetimes);


        $this->assertEquals(
            '2024-01-01 07:00:00 Asia/Jakarta',
            $target->datetimes[0],
        );

        $this->assertEquals(
            '2024-02-01 07:00:00 Asia/Jakarta',
            $target->datetimes[1],
        );

        $this->assertEquals(
            '2024-03-01 07:00:00 Asia/Jakarta',
            $target->datetimes[2],
        );
    }

    /**
     * @param class-string<DateTimeTestObjectInterface> $sourceClass
     * @param class-string<DateTimeTestObjectInterface> $targetClass
     * @dataProvider dateTimeMappingProvider
     */
    public function testDateTimeMapping(
        string $sourceClass,
        string $targetClass,
        string|int|float $expected,
        ?string $format = null,
    ): void {
        $source = $sourceClass::preinitialized();
        $target = $this->mapper->map($source, $targetClass);

        $this->assertInstanceOf($targetClass, $target);

        /** @var mixed */
        $result = $target->getProperty();
        $this->assertNotNull($result);

        if ($result instanceof \DateTimeInterface) {
            $this->assertEquals($expected, $result->format($format ?? 'Y-m-d H:i:s e'));
        } else {
            $this->assertIsScalar($result);
            $this->assertEquals($expected, (string) $result);
        }
    }

    /**
     * @return iterable<array-key,array{class-string<DateTimeTestObjectInterface>,class-string<DateTimeTestObjectInterface>,string|int|float,?string}>
     */
    public static function dateTimeMappingProvider(): iterable
    {
        $objectsWithDateTime = [
            ObjectWithDateTimeInterface::class,
            ObjectWithDateTimeImmutable::class,
            ObjectWithDateTime::class,
            ObjectWithDatePoint::class,
        ];

        $objectsWithScalar = [
            ObjectWithString::class,
            ObjectWithInt::class,
            ObjectWithFloat::class,
        ];

        // object datetime to scalar

        foreach ($objectsWithDateTime as $objectWithDateTime) {
            yield
                self::getDescription($objectWithDateTime, ObjectWithString::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithString::class,
                    '2024-01-01T12:00:00+00:00',
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithStringWithTimeZone::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithStringWithTimeZone::class,
                    '2024-01-01T19:00:00+07:00',
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithStringWithFormat::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithStringWithFormat::class,
                    'Mon, 01 Jan 24 12:00:00 +0000',
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithStringWithTimeZoneAndFormat::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithStringWithTimeZoneAndFormat::class,
                    'Mon, 01 Jan 24 19:00:00 +0700',
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithInt::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithInt::class,
                    1704110400,
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithIntYYYYMMDD::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithIntYYYYMMDD::class,
                    20240101,
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithFloat::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithFloat::class,
                    (float) 1704110400,
                    null,
                ];

            yield
                self::getDescription($objectWithDateTime, ObjectWithFloatYYYYMMDD::class) =>
                [
                    $objectWithDateTime,
                    ObjectWithFloatYYYYMMDD::class,
                    20240101.0,
                    null,
                ];

            // formatting at the input

            yield
                self::getDescription(ObjectWithStringDDMMYYYY::class, $objectWithDateTime) =>
                [
                    ObjectWithStringDDMMYYYY::class,
                    $objectWithDateTime,
                    '2023-01-01 UTC',
                    'Y-m-d e',
                ];

            yield
                self::getDescription(ObjectWithStringDDMMYYYYWithTimeZone::class, $objectWithDateTime) =>
                [
                    ObjectWithStringDDMMYYYYWithTimeZone::class,
                    $objectWithDateTime,
                    '2023-01-01 Asia/Jakarta',
                    'Y-m-d e',
                ];

            yield
                self::getDescription(ObjectWithIntYYYYMMDD::class, $objectWithDateTime) =>
                [
                    ObjectWithIntYYYYMMDD::class,
                    $objectWithDateTime,
                    '2022-01-05 UTC',
                    'Y-m-d e',
                ];
        }

        // scalar to datetime

        foreach ($objectsWithScalar as $objectWithScalar) {
            foreach ($objectsWithDateTime as $objectWithDateTime) {
                yield
                    self::getDescription($objectWithScalar, $objectWithDateTime) =>
                    [
                        $objectWithScalar,
                        $objectWithDateTime,
                        '2024-01-01 12:00:00 UTC',
                        null,
                    ];
            }
        }
    }

    private static function getShortClass(string $class): string
    {
        $parts = explode('\\', $class);

        return array_pop($parts);
    }

    private static function getDescription(string $source, string $target): string
    {
        return \sprintf('%s to %s', self::getShortClass($source), self::getShortClass($target));
    }
}
