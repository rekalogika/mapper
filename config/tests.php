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

use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Tests\Common\TestKernel;
use Rekalogika\Mapper\Tests\Fixtures\Money\MoneyToMoneyDtoTransformer;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyObjectMapper;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\PersonToPersonDtoMapper;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttributeWithoutExplicitProperty;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithExtraArguments;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\RememberingMapper\RememberingMapper;
use Rekalogika\Mapper\Tests\Fixtures\TransformerOverride\OverrideTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    // add test aliases
    $serviceIds = TestKernel::getServiceIds();

    foreach ($serviceIds as $serviceId) {
        $services->alias('test.' . $serviceId, $serviceId)->public();
    };

    $services->set(PropertyMapperWithoutClassAttribute::class);
    $services->set(PropertyMapperWithClassAttribute::class);
    $services->set(PropertyMapperWithConstructorWithoutClassAttribute::class);
    $services->set(PropertyMapperWithConstructorWithClassAttribute::class);
    $services->set(PropertyMapperWithClassAttributeWithoutExplicitProperty::class);
    $services->set(PropertyMapperWithExtraArguments::class);
    $services->set(MoneyObjectMapper::class);
    $services->set(PersonToPersonDtoMapper::class);

    $services->set(MoneyToMoneyDtoTransformer::class)
        ->tag('rekalogika.mapper.transformer');
    $services->set(OverrideTransformer::class)
        ->tag('rekalogika.mapper.transformer')
        ->args([
            '$transformer' => service(ScalarToScalarTransformer::class),
        ]);

    $services->set(RememberingMapper::class)
        ->args([
            '$decorated' => service(MapperInterface::class),
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
        ]);
};
