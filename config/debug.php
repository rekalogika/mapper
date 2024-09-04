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

use Rekalogika\Mapper\Debug\MapperDataCollector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->set('rekalogika.mapper.data_collector', MapperDataCollector::class)
        ->tag('data_collector', [
            'id' => 'rekalogika_mapper',
        ])
        ->tag('kernel.reset', ['method' => 'reset']);
};
