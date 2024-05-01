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
use Rekalogika\Mapper\Tests\Fixtures\Remove\MemberDto;
use Rekalogika\Mapper\Tests\Fixtures\Remove\MemberRepository;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArray;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayDto;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayWithoutAllowDeleteAttribute;

/** @psalm-suppress MissingConstructor */
class RemoveTest extends FrameworkTestCase
{
    private MemberRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->get(MemberRepository::class);
        $this->repository->add('1');
        $this->repository->add('2');
        $this->repository->add('3');
    }

    public function testAdd(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        $objectWithArrayDto->members[] = new MemberDto('3');

        $objectWithArray = $this->mapper->map($objectWithArrayDto, ObjectWithArray::class);

        $this->assertCount(3, $objectWithArray->members);
        $this->assertSame('1', $objectWithArray->members[0]->getId());
        $this->assertSame('2', $objectWithArray->members[1]->getId());
        $this->assertSame('3', $objectWithArray->members[2]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->members[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->members[1]);
        $this->assertSame($this->repository->get('3'), $objectWithArray->members[2]);
    }

    public function testRemove(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithArray();
        $objectWithArray->members[] = $this->repository->get('1');
        $objectWithArray->members[] = $this->repository->get('2');
        $objectWithArray->members[] = $this->repository->get('3');
        $this->assertCount(3, $objectWithArray->members);

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->members);
        $this->assertSame('1', $objectWithArray->members[0]->getId());
        $this->assertSame('2', $objectWithArray->members[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->members[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->members[1]);
    }

    public function testNoRemovalWithoutAllowDeleteAttribute(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithArrayWithoutAllowDeleteAttribute();
        $objectWithArray->members[] = $this->repository->get('1');
        $objectWithArray->members[] = $this->repository->get('2');
        $objectWithArray->members[] = $this->repository->get('3');
        $this->assertCount(3, $objectWithArray->members);

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(3, $objectWithArray->members);
        $this->assertSame('1', $objectWithArray->members[0]->getId());
        $this->assertSame('2', $objectWithArray->members[1]->getId());
        $this->assertSame('3', $objectWithArray->members[2]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->members[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->members[1]);
        $this->assertSame($this->repository->get('3'), $objectWithArray->members[2]);
    }
}
