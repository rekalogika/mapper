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

namespace Rekalogika\Mapper\Tests\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\ErrorHandler\ErrorHandler;

// workaround, @see https://github.com/symfony/symfony/issues/53812#issuecomment-1962311843
ErrorHandler::register(null, false);

class PHPUnitExtension implements Extension
{
    #[\Override]
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $facade->registerSubscriber(new MapperPreparationStartedSubscriber());
        $facade->registerSubscriber(new MapperErroredSubscriber());
    }
}
