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

namespace Rekalogika\Mapper\Tests\Fixtures\MethodMapper;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MethodMapper\MapFromObjectInterface;
use Rekalogika\Mapper\MethodMapper\MapToObjectInterface;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

/**
 * @deprecated
 * @psalm-suppress DeprecatedInterface
 */
final class ObjectWithObjectWithScalarPropertiesDto implements
    MapFromObjectInterface,
    MapToObjectInterface
{
    public ?ObjectWithScalarPropertiesDto $objectWithScalarProperties = null;



    #[\Override]
    public static function mapFromObject(
        object $source,
        SubMapperInterface $mapper,
        Context $context
    ): static {
        \assert($source instanceof ObjectWithObjectWithScalarProperties);

        $self = new self();

        $self->objectWithScalarProperties = $mapper->map(
            $source->objectWithScalarProperties,
            ObjectWithScalarPropertiesDto::class,
            $context
        );

        return $self;
    }

    #[\Override]
    public function mapToObject(
        object|string $target,
        SubMapperInterface $mapper,
        Context $context
    ): object {
        if ($target === ObjectWithObjectWithScalarProperties::class) {
            $target = new $target();
        }

        \assert($target instanceof ObjectWithObjectWithScalarProperties);
        \assert($this->objectWithScalarProperties instanceof ObjectWithScalarPropertiesDto);

        $target->objectWithScalarProperties = $mapper->map(
            $this->objectWithScalarProperties,
            ObjectWithScalarProperties::class,
            $context
        );

        return $target;
    }
}
