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

use Rekalogika\Mapper\Tests\Common\TestKernel;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithoutClassAttribute;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // add test aliases
    $serviceIds = TestKernel::getServiceIds();

    foreach ($serviceIds as $serviceId) {
        $services->alias('test.' . $serviceId, $serviceId)->public();
    };

    $services->set(PropertyMapperWithoutClassAttribute::class)
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(PropertyMapperWithClassAttribute::class)
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(PropertyMapperWithConstructorWithoutClassAttribute::class)
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(PropertyMapperWithConstructorWithClassAttribute::class)
        ->autowire()
        ->autoconfigure()
        ->public();
};
