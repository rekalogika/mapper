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

class ObjectWithAdderRemover
{
    /**
     * @var array<int,Member>
     */
    #[AllowDelete]
    private array $members = [];

    /**
     * @return array<int,Member>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function addMember(Member $member): void
    {
        if (!in_array($member, $this->members, true)) {
            $this->members[] = $member;
        }
    }

    public function removeMember(Member $member): void
    {
        $key = array_search($member, $this->members, true);

        if (false !== $key) {
            unset($this->members[$key]);
        }
    }
}
