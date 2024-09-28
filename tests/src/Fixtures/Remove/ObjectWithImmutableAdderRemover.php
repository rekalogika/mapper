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

use Rekalogika\Mapper\Attribute\AllowDelete;

readonly class ObjectWithImmutableAdderRemover
{
    /**
     * @param array<int,Member> $members
     */
    public function __construct(
        #[AllowDelete]
        private array $members = [],
    ) {}

    /**
     * @return array<int,Member>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function addMember(Member $member): self
    {
        $members = $this->members;

        if (!\in_array($member, $this->members, true)) {
            $members[] = $member;
        }

        return new self($members);
    }

    public function removeMember(Member $member): self
    {
        $members = $this->members;
        $key = array_search($member, $members, true);

        if (false !== $key) {
            unset($members[$key]);
        }

        return new self($members);
    }
}
