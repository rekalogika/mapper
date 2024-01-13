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

use Rekalogika\Mapper\MethodMapper\MapFromObjectInterface;
use Rekalogika\Mapper\MethodMapper\SubMapperInterface;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;

final class ObjectWithArrayPropertyDto implements MapFromObjectInterface
{
    /**
     * @var ?array<int,ObjectWithScalarPropertiesDto>
     */
    public ?array $property = null;

    public static function mapFromObject(
        object $source,
        SubMapperInterface $mapper,
        array $context = []
    ): static {
        assert($source instanceof ObjectWithCollectionProperty);

        $result = new self();

        /** @var array<int,ObjectWithScalarPropertiesDto>|null $property */
        $property = $mapper->mapForProperty(
            $source->property,
            ObjectWithArrayPropertyDto::class,
            'property',
            $context
        );

        $result->property = $property;

        return $result;
    }
}
