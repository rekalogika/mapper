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

namespace Rekalogika\Mapper\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SimpleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column()]
    private ?string $name = null;

    public function getId(): int
    {
        if (null === $this->id) {
            throw new \LogicException('The identifier is not set.');
        }

        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
