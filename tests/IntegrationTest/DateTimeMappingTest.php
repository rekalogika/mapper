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

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Symfony\Component\Clock\DatePoint;

class DateTimeMappingTest extends AbstractIntegrationTest
{
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
    public function dateTimeProvider(): iterable
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
                yield sprintf("%s to %s", $sourceClass, $targetClass) => [$sourceClass, $targetClass];
            }
        }
    }
}
