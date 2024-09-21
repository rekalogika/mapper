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

namespace Rekalogika\Mapper\Tests\Fixtures\Remove;

use Rekalogika\Mapper\Attribute\AllowTargetDelete;

class ObjectWithArrayDtoWithAllowTargetDeleteAttributeAttachedToGetter
{
    /**
     * @var array<int,MemberDto>
     */
    private array $members = [];

    /**
     * @return array<int,MemberDto>
     */
    #[AllowTargetDelete]
    public function getMembers(): array
    {
        return $this->members;
    }

    public function addMember(MemberDto $member): void
    {
        if (!\in_array($member, $this->members, true)) {
            $this->members[] = $member;
        }
    }
}
