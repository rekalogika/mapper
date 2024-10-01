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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractor;

use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\Attributes;

/**
 * @internal
 */
final class CachingAttributesExtractor implements AttributesExtractorInterface
{
    /**
     * @var array<class-string,Attributes>
     */
    private array $classAttributesCache = [];

    /**
     * @var array<class-string,array<string,Attributes>>
     */
    private array $propertyAttributesCache = [];

    public function __construct(
        private AttributesExtractorInterface $decorated,
    ) {}

    public function getClassAttributes(string $class): Attributes
    {
        return $this->classAttributesCache[$class]
            ??= $this->decorated->getClassAttributes($class);
    }

    public function getPropertyAttributes(string $class, string $property): Attributes
    {
        return $this->propertyAttributesCache[$class][$property]
            ??= $this->decorated->getPropertyAttributes($class, $property);
    }
}
