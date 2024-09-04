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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->set('rekalogika.mapper.cache.property_info')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.property_info.cache', PropertyInfoCacheExtractor::class)
        ->decorate('rekalogika.mapper.property_info')
        ->args([
            service('rekalogika.mapper.property_info.cache.inner'),
            service('rekalogika.mapper.cache.property_info'),
        ]);
};
