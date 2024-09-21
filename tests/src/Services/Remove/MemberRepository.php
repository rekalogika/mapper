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

use Rekalogika\Mapper\Tests\Fixtures\Remove\Member;

class MemberRepository
{
    /**
     * @var array<string,Member>
     */
    private array $members = [];

    public function add(string $id): Member
    {
        $member = new Member($id);
        $this->members[$id] = $member;

        return $member;
    }

    public function get(string $id): Member
    {
        return $this->members[$id] ?? throw new \InvalidArgumentException('Member not found');
    }
}
