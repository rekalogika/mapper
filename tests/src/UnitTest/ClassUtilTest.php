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

namespace Rekalogika\Mapper\Tests\UnitTest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\SimpleEntity;
use Rekalogika\Mapper\Util\ClassUtil;

class ClassUtilTest extends TestCase
{
    #[DataProvider('provideProxyClassToRealClass')]
    public function testProxyClassToRealClass(string $proxyClass, string $realClass): void
    {
        $this->assertSame(
            $realClass,
            ClassUtil::getRealClassName($proxyClass),
        );
    }

    /**
     * @return iterable<array-key,array{string,string}>
     */
    public static function provideProxyClassToRealClass(): iterable
    {
        yield 'DoctrineORM' => [
            'Proxies\__CG__\Rekalogika\Mapper\Tests\Fixtures\Doctrine\SimpleEntity',
            SimpleEntity::class,
        ];

        yield 'MongoDB' => [
            'MongoDBODMProxies\__PM__\Rekalogika\Mapper\Tests\Fixtures\Doctrine\SimpleEntity\Generated93deedc1e7b56ba9c8d5a337a376eda9',
            SimpleEntity::class,
        ];

        yield 'NoProxy' => [
            SimpleEntity::class,
            SimpleEntity::class,
        ];
    }
}
