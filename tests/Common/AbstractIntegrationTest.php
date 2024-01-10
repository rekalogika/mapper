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

namespace Rekalogika\Mapper\Tests\Common;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\MainTransformer;
use Rekalogika\Mapper\MapperInterface;

abstract class AbstractIntegrationTest extends TestCase
{
    protected MapperTestFactory $factory;
    protected MapperInterface $mapper;
    protected MainTransformer $mainTransformer;

    public function setUp(): void
    {
        $this->factory = new MapperTestFactory();
        $this->mapper = $this->factory->getMapper();
        $this->mainTransformer = $this->factory->getMainTransformer();
    }
}
