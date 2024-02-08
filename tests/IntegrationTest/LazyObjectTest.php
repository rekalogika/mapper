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

use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ChildObjectWithIdDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ConcreteObjectWithId;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithId;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdFinalDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdReadOnlyDto;

class LazyObjectTest extends AbstractFrameworkTest
{
    public function testLazyObject(): void
    {
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdDto::class);
        $this->assertSame('id', $target->id);
    }

    public function testLazyObjectHydration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method should not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdDto::class);
        $this->initialize($target);
    }

    public function testFinal(): void
    {
        // final class can't be lazy
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method should not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdFinalDto::class);
        $this->initialize($target);
    }

    /**
     * In PHP 8.2, readonly class can't be lazy
     *
     * @requires PHP >= 8.2.0
     * @requires PHP < 8.3.0
     */
    public function testReadOnly82(): void
    {
        // should not use proxy. if a proxy is not used, it should throw an
        // exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method should not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdReadOnlyDto::class);
    }

    /**
     * In PHP 8.3, readonly class can be lazy
     *
     * @requires PHP >= 8.3.0
     */
    public function testReadOnly83(): void
    {
        // if a proxy is used, it should not throw an exception without
        // initialization
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdReadOnlyDto::class);
    }


    public function testIdInParentClass(): void
    {
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ChildObjectWithIdDto::class);
        $this->assertSame('id', $target->id);
    }

    public function testIdInParentClassInitialized(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method should not be called');

        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ChildObjectWithIdDto::class);
        $foo = $target->name;
    }
}
