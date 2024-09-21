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

namespace Rekalogika\Mapper\Tests\Services\Remove;

use Rekalogika\Mapper\Attribute\AsObjectMapper;
use Rekalogika\Mapper\Tests\Fixtures\Remove\Member;
use Rekalogika\Mapper\Tests\Fixtures\Remove\MemberDto;

class MemberDtoToMemberMapper
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
    ) {}

    #[AsObjectMapper]
    public function mapMemberDtoToMember(MemberDto $memberDto): Member
    {
        $id = $memberDto->id;

        return $this->memberRepository->get($id);
    }
}
