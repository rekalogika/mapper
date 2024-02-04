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

namespace Rekalogika\Mapper\ServiceMethod;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;

class ServiceMethodRunner
{
    public static function create(
        ContainerInterface $serviceLocator,
        MainTransformerInterface $mainTransformer
    ): self {
        return new self($serviceLocator, $mainTransformer);
    }

    private function __construct(
        private ContainerInterface $serviceLocator,
        private MainTransformerInterface $mainTransformer
    ) {
    }

    public function run(
        ServiceMethodSpecification $serviceMethodSpecification,
        mixed $input,
        Context $context,
    ): mixed {
        /** @var object */
        $service = $this->serviceLocator->get($serviceMethodSpecification->getServiceId());
        $method = $serviceMethodSpecification->getMethod();
        $extraArguments = $serviceMethodSpecification->getExtraArguments();

        $arguments = [];

        foreach ($extraArguments as $extraArgument) {
            $arguments[] = match ($extraArgument) {
                ServiceMethodSpecification::ARGUMENT_CONTEXT => $context,
                ServiceMethodSpecification::ARGUMENT_MAIN_TRANSFORMER => $this->mainTransformer,
                default => throw new LogicException('Unknown extra argument: ' . $extraArgument, context: $context),
            };
        }

        /** @psalm-suppress MixedMethodCall */
        return $service->{$method}($input, ...$arguments);
    }
}
