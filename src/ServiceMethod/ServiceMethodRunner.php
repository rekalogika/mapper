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
use Rekalogika\Mapper\Exception\RefuseToMapException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class ServiceMethodRunner
{
    public static function create(
        ContainerInterface $serviceLocator,
        MainTransformerInterface $mainTransformer,
        SubMapperFactoryInterface $subMapperFactory,
    ): self {
        return new self($serviceLocator, $mainTransformer, $subMapperFactory);
    }

    private function __construct(
        private ContainerInterface $serviceLocator,
        private MainTransformerInterface $mainTransformer,
        private SubMapperFactoryInterface $subMapperFactory,
    ) {}

    public function runObjectMapper(
        ServiceMethodSpecification $serviceMethodSpecification,
        mixed $source,
        mixed $target,
        ?Type $targetType,
        Context $context,
    ): mixed {
        /** @var object */
        $service = $this->serviceLocator->get($serviceMethodSpecification->getServiceId());
        $method = $serviceMethodSpecification->getMethod();

        $arguments = [$source];

        if ($serviceMethodSpecification->hasExistingTarget()) {
            /** @psalm-suppress MixedAssignment */
            $arguments[] = $target;
        }

        $arguments = [
            ...$arguments,
            ...$this->createExtraArguments(
                serviceMethodSpecification: $serviceMethodSpecification,
                context: $context,
                source: $source,
                targetType: $targetType,
            ),
        ];

        /** @psalm-suppress MixedMethodCall */
        return $service->{$method}(...$arguments);
    }

    public function runPropertyMapper(
        ServiceMethodSpecification $serviceMethodSpecification,
        mixed $source,
        mixed $target,
        mixed $targetPropertyValue,
        ?Type $targetType,
        Context $context,
    ): mixed {
        /** @var object */
        $service = $this->serviceLocator->get($serviceMethodSpecification->getServiceId());
        $method = $serviceMethodSpecification->getMethod();

        $arguments = [$source];

        if ($serviceMethodSpecification->hasExistingTarget()) {
            /** @psalm-suppress MixedAssignment */
            $arguments[] = $targetPropertyValue;
        }

        $arguments = [
            ...$arguments,
            ...$this->createExtraArguments(
                serviceMethodSpecification: $serviceMethodSpecification,
                context: $context,
                source: $source,
                targetType: $targetType,
            ),
        ];

        try {
            /** @psalm-suppress MixedMethodCall */
            return $service->{$method}(...$arguments);
        } catch (\Error $e) {
            if (
                $serviceMethodSpecification->ignoreUninitialized()
                && str_contains($e->getMessage(), 'must not be accessed before initialization')
            ) {
                throw new RefuseToMapException();
            }

            throw $e;
        }
    }

    /**
     * @return list<mixed>
     */
    private function createExtraArguments(
        ServiceMethodSpecification $serviceMethodSpecification,
        Context $context,
        mixed $source,
        ?Type $targetType,
    ): array {
        $extraArguments = $serviceMethodSpecification->getExtraArguments();

        $arguments = [];

        foreach ($extraArguments as $extraArgument) {
            $arguments[] = match ($extraArgument) {
                ServiceMethodSpecification::ARGUMENT_CONTEXT => $context,
                ServiceMethodSpecification::ARGUMENT_MAIN_TRANSFORMER => $this->mainTransformer,
                ServiceMethodSpecification::ARGUMENT_SUB_MAPPER => $this->subMapperFactory->createSubMapper(
                    mainTransformer: $this->mainTransformer,
                    source: $source,
                    targetType: $targetType,
                    context: $context,
                ),
            };
        }

        return $arguments;
    }
}
