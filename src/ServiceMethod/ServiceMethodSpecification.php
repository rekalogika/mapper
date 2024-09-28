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

/**
 * @internal
 */
final readonly class ServiceMethodSpecification
{
    public const ARGUMENT_CONTEXT = 'context';

    public const ARGUMENT_MAIN_TRANSFORMER = 'main_transformer';

    public const ARGUMENT_SUB_MAPPER = 'sub_mapper';

    /**
     * @param array<int,self::ARGUMENT_*> $extraArguments
     */
    public function __construct(
        private string $serviceId,
        private string $method,
        private bool $hasExistingTarget,
        private array $extraArguments,
    ) {}

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array<int,self::ARGUMENT_*>
     */
    public function getExtraArguments(): array
    {
        return $this->extraArguments;
    }

    public function hasExistingTarget(): bool
    {
        return $this->hasExistingTarget;
    }
}
