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
use Rekalogika\Mapper\Tests\Fixtures\Recursive\ChildObject;
use Rekalogika\Mapper\Tests\Fixtures\Recursive\ObjectWithRefToItself;
use Rekalogika\Mapper\Tests\Fixtures\Recursive\ParentObject;
use Rekalogika\Mapper\Tests\Fixtures\Recursive\SelfReferencing;
use Rekalogika\Mapper\Tests\Fixtures\RecursiveDto\ChildObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\RecursiveDto\ObjectWithRefToItselfDto;
use Rekalogika\Mapper\Tests\Fixtures\RecursiveDto\ParentObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\RecursiveDto\SelfReferencingDto;

class RecursionTest extends FrameworkTestCase
{
    public function testParentChild(): void
    {
        $parent = new ParentObject();
        $child = new ChildObject();
        $parent->child = $child;

        $child->parent = $parent;

        $result = $this->mapper->map($parent, ParentObjectDto::class);

        $this->assertInstanceOf(ParentObjectDto::class, $result);
        $this->assertInstanceOf(ChildObjectDto::class, $result->child);
        $this->assertInstanceOf(ParentObjectDto::class, $result->child->parent);
        $this->assertSame($result, $result->child->parent);
    }

    public function testCircular(): void
    {
        $object1 = new ObjectWithRefToItself();
        $object1->string = '1';

        $object2 = new ObjectWithRefToItself();
        $object2->string = '2';
        $object1->ref = $object2;

        $object3 = new ObjectWithRefToItself();
        $object3->string = '3';
        $object2->ref = $object3
        ;
        $object4 = new ObjectWithRefToItself();
        $object4->string = '4';
        $object3->ref = $object4;

        $object5 = new ObjectWithRefToItself();
        $object5->string = '5';
        $object4->ref = $object5;
        $object5->ref = $object1;

        $result = $this->mapper->map($object1, ObjectWithRefToItselfDto::class);

        $this->assertInstanceOf(ObjectWithRefToItselfDto::class, $result);
        $this->assertInstanceOf(ObjectWithRefToItselfDto::class, $result->ref);
        $this->assertInstanceOf(ObjectWithRefToItselfDto::class, $result->ref->ref);
        $this->assertInstanceOf(ObjectWithRefToItselfDto::class, $result->ref->ref->ref);
        $this->assertInstanceOf(ObjectWithRefToItselfDto::class, $result->ref->ref->ref->ref);
        $this->assertSame($result, $result->ref->ref->ref->ref->ref);

        $this->assertSame('1', $result->string);
        // @phpstan-ignore-next-line
        $this->assertSame('2', $result->ref->string);
        // @phpstan-ignore-next-line
        $this->assertSame('3', $result->ref->ref->string);
        // @phpstan-ignore-next-line
        $this->assertSame('4', $result->ref->ref->ref->string);
        // @phpstan-ignore-next-line
        $this->assertSame('5', $result->ref->ref->ref->ref->string);
    }

    public function testSelfReferencing(): void
    {
        $object = new SelfReferencing();

        $result = $this->mapper->map($object, SelfReferencingDto::class);

        $this->assertInstanceOf(SelfReferencingDto::class, $result);
        $this->assertInstanceOf(SelfReferencingDto::class, $result->child);
        $this->assertSame($result, $result->child);
    }
}
