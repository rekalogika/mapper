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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;

/**
 * @internal
 */
final readonly class ServiceMethodExtraArgumentUtil
{
    private function __construct()
    {
    }

    /**
     * @param class-string $serviceClass
     * @return array<int,ServiceMethodSpecification::ARGUMENT_*>
     */
    public static function getExtraArguments(
        string $serviceClass,
        string $method
    ): array {
        $reflectionClass = new \ReflectionClass($serviceClass);
        $parameters = $reflectionClass->getMethod($method)->getParameters();
        // remove first element, which is always the source class
        array_shift($parameters);

        $extraArguments = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Extra arguments for property mapper "%s" in class "%s" must be type hinted.',
                        $method,
                        $serviceClass,
                    )
                );
            }

            $extraArguments[] = match ($type->getName()) {
                Context::class => ServiceMethodSpecification::ARGUMENT_CONTEXT,
                MainTransformerInterface::class => ServiceMethodSpecification::ARGUMENT_MAIN_TRANSFORMER,
                SubMapperInterface::class => ServiceMethodSpecification::ARGUMENT_SUB_MAPPER,
                default => throw new InvalidArgumentException(
                    sprintf(
                        'Extra argument with type "%s" for property mapper "%s" in class "%s" is unsupported.',
                        $type->getName(),
                        $method,
                        $serviceClass,
                    )
                )
            };
        }

        return $extraArguments;
    }
}
