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
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithConstructorArgumentsAndGetters;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithConstructorArgumentsAndPublicProperties;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithIdOnlyOnConstructor;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\SourceObject;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ConstructorAndPropertyTest extends FrameworkTestCase
{
    /**
     * lazy constructor arguments
     */
    public function testToConstructorArgumentsAndGetters(): void
    {
        $source = new SourceObject();
        $result = $this->mapper->map($source, ObjectWithConstructorArgumentsAndGetters::class);
        $this->assertInstanceOf(LazyObjectInterface::class, $result);
        $this->assertFalse($result->isLazyObjectInitialized());
        $this->assertSame('id', $result->getId());
        $this->assertSame('name', $result->getName());
        $this->assertSame('description', $result->getDescription());
    }

    /**
     * eager constructor arguments
     */
    public function testToIdOnlyOnConstructor(): void
    {
        $source = new SourceObject();
        $result = $this->mapper->map($source, ObjectWithIdOnlyOnConstructor::class);
        $this->assertInstanceOf(LazyObjectInterface::class, $result);
        $this->assertFalse($result->isLazyObjectInitialized());
        $this->assertSame('id', $result->getId());
        $this->assertSame('name', $result->getName());
        $this->assertSame('description', $result->getDescription());
    }

    /**
     * eager constructor arguments
     */
    public function testToConstructorArgumentsAndPublicProperties(): void
    {
        $source = new SourceObject();
        $result = $this->mapper->map($source, ObjectWithConstructorArgumentsAndPublicProperties::class);
        $this->assertInstanceOf(LazyObjectInterface::class, $result);
        $this->assertFalse($result->isLazyObjectInitialized());
        $this->assertSame('id', $result->id);
        $this->assertSame('name', $result->name);
        $this->assertSame('description', $result->description);
    }
}
