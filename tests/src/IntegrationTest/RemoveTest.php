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
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithAdderRemover;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithAdderRemoverWithAllowDeleteAttachedToGetter;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithAdderRemoverWithAllowDeleteAttachedToRemover;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArray;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayDto;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayDtoWithAllowTargetDeleteAttribute;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayDtoWithAllowTargetDeleteAttributeAttachedToGetter;
use Rekalogika\Mapper\Tests\Fixtures\Remove\ObjectWithArrayWithoutAllowDeleteAttribute;
use Rekalogika\Mapper\Tests\Services\Remove\MemberRepository;

/** @psalm-suppress MissingConstructor */
class RemoveTest extends FrameworkTestCase
{
    private MemberRepository $repository;

    #[\Override]
    protected function setUp(): void
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

    public function testRemoveFromArray(): void
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

    public function testRemoveFromArrayWithSourceAttribute(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDtoWithAllowTargetDeleteAttribute();
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

    public function testRemoveUsingRemover(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithAdderRemover();
        $objectWithArray->addMember($this->repository->get('1'));
        $objectWithArray->addMember($this->repository->get('2'));
        $objectWithArray->addMember($this->repository->get('3'));
        $this->assertCount(3, $objectWithArray->getMembers());

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->getMembers());
        $this->assertSame('1', $objectWithArray->getMembers()[0]->getId());
        $this->assertSame('2', $objectWithArray->getMembers()[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->getMembers()[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->getMembers()[1]);
    }

    public function testRemoveUsingRemoverWithAllowDeleteAttachedToGetter(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithAdderRemoverWithAllowDeleteAttachedToGetter();
        $objectWithArray->addMember($this->repository->get('1'));
        $objectWithArray->addMember($this->repository->get('2'));
        $objectWithArray->addMember($this->repository->get('3'));
        $this->assertCount(3, $objectWithArray->getMembers());

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->getMembers());
        $this->assertSame('1', $objectWithArray->getMembers()[0]->getId());
        $this->assertSame('2', $objectWithArray->getMembers()[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->getMembers()[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->getMembers()[1]);
    }

    public function testRemoveUsingRemoverWithAllowDeleteAttachedToRemover(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDto();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithAdderRemoverWithAllowDeleteAttachedToRemover();
        $objectWithArray->addMember($this->repository->get('1'));
        $objectWithArray->addMember($this->repository->get('2'));
        $objectWithArray->addMember($this->repository->get('3'));
        $this->assertCount(3, $objectWithArray->getMembers());

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->getMembers());
        $this->assertSame('1', $objectWithArray->getMembers()[0]->getId());
        $this->assertSame('2', $objectWithArray->getMembers()[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->getMembers()[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->getMembers()[1]);
    }

    public function testRemoveUsingRemoverWithSourceAttribute(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDtoWithAllowTargetDeleteAttribute();
        $objectWithArrayDto->members[] = new MemberDto('1');
        $objectWithArrayDto->members[] = new MemberDto('2');
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithAdderRemover();
        $objectWithArray->addMember($this->repository->get('1'));
        $objectWithArray->addMember($this->repository->get('2'));
        $objectWithArray->addMember($this->repository->get('3'));
        $this->assertCount(3, $objectWithArray->getMembers());

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->getMembers());
        $this->assertSame('1', $objectWithArray->getMembers()[0]->getId());
        $this->assertSame('2', $objectWithArray->getMembers()[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->getMembers()[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->getMembers()[1]);
    }

    public function testRemoveUsingRemoverWithSourceAttributeAttachedToGetter(): void
    {
        $objectWithArrayDto = new ObjectWithArrayDtoWithAllowTargetDeleteAttributeAttachedToGetter();
        $objectWithArrayDto->addMember(new MemberDto('1'));
        $objectWithArrayDto->addMember(new MemberDto('2'));
        // 3 is missing, and this should remove 3 from the target object

        $objectWithArray = new ObjectWithAdderRemover();
        $objectWithArray->addMember($this->repository->get('1'));
        $objectWithArray->addMember($this->repository->get('2'));
        $objectWithArray->addMember($this->repository->get('3'));
        $this->assertCount(3, $objectWithArray->getMembers());

        $this->mapper->map($objectWithArrayDto, $objectWithArray);

        $this->assertCount(2, $objectWithArray->getMembers());
        $this->assertSame('1', $objectWithArray->getMembers()[0]->getId());
        $this->assertSame('2', $objectWithArray->getMembers()[1]->getId());
        $this->assertSame($this->repository->get('1'), $objectWithArray->getMembers()[0]);
        $this->assertSame($this->repository->get('2'), $objectWithArray->getMembers()[1]);
    }
}
