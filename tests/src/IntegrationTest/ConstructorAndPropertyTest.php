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
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithConstructorAndSetter;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithConstructorArgumentsAndGetters;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithConstructorArgumentsAndPublicProperties;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\ObjectWithIdOnlyOnConstructor;
use Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty\SourceObject;

class ConstructorAndPropertyTest extends FrameworkTestCase
{
    /**
     * lazy constructor arguments
     */
    public function testToConstructorArgumentsAndGetters(): void
    {
        $source = new SourceObject();
        $result = $this->mapper->map($source, ObjectWithConstructorArgumentsAndGetters::class);

        $this->assertIsUninitializedProxy($result);

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

        $this->assertIsUninitializedProxy($result);

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

        $this->assertIsUninitializedProxy($result);

        $this->assertSame('id', $result->id);
        $this->assertSame('name', $result->name);
        $this->assertSame('description', $result->description);
    }

    public function testToConstructorAndSetter(): void
    {
        $source = new SourceObject();
        $result = $this->mapper->map($source, ObjectWithConstructorAndSetter::class);

        $this->assertIsUninitializedProxy($result);

        $this->assertSame('id', $result->getId());
        $this->assertSame('name', $result->getName());
        $this->assertSame('description', $result->getDescription());
    }
}
