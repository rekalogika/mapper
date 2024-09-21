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

namespace Rekalogika\Mapper\Tests\FrameworkTest;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Tests\Common\TestKernel;

class FrameworkTest extends TestCase
{
    private ?ContainerInterface $container = null;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        $this->container = $kernel->getContainer();
    }

    public function testWiring(): void
    {
        foreach (TestKernel::getServiceIds() as $serviceId) {
            $service = $this->container?->get('test.' . $serviceId);

            $this->assertIsObject($service);
        }
    }
}
