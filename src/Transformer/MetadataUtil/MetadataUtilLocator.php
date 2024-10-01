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

namespace Rekalogika\Mapper\Transformer\MetadataUtil;

use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\ObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final class MetadataUtilLocator
{
    private ?DynamicPropertiesDeterminer $dynamicPropertiesDeterminer = null;
    private ?PropertyAccessInfoExtractor $propertyAccessInfoExtractor = null;
    private ?AttributesExtractor $attributesExtractor = null;
    private ?PropertyMetadataFactory $propertyMetadataFactory = null;
    private ?ClassMetadataFactory $classMetadataFactory = null;
    private ?UnalterableDeterminer $unalterableDeterminer = null;
    private ?PropertyMappingResolver $propertyMappingResolver = null;
    private ?ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory = null;

    public function __construct(
        private readonly PropertyListExtractorInterface $propertyListExtractor,
        private readonly PropertyTypeExtractorInterface $propertyTypeExtractor,
        private readonly PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private readonly PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        private readonly TypeResolverInterface $typeResolver,
        private readonly EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private readonly ProxyFactoryInterface $proxyFactory,
        private readonly PropertyMapperResolverInterface $propertyMapperResolver,
    ) {}

    private function getDynamicPropertiesDeterminer(): DynamicPropertiesDeterminer
    {
        return $this->dynamicPropertiesDeterminer
            ??= new DynamicPropertiesDeterminer();
    }

    private function getPropertyAccessInfoExtractor(): PropertyAccessInfoExtractor
    {
        return $this->propertyAccessInfoExtractor
            ??= new PropertyAccessInfoExtractor(
                propertyReadInfoExtractor: $this->propertyReadInfoExtractor,
                propertyWriteInfoExtractor: $this->propertyWriteInfoExtractor,
            );
    }

    private function getAttributesExtractor(): AttributesExtractor
    {
        return $this->attributesExtractor
            ??= new AttributesExtractor(
                propertyAccessInfoExtractor: $this->getPropertyAccessInfoExtractor(),
            );
    }

    private function getPropertyMetadataFactory(): PropertyMetadataFactory
    {
        return $this->propertyMetadataFactory
            ??= new PropertyMetadataFactory(
                propertyAccessInfoExtractor: $this->getPropertyAccessInfoExtractor(),
                propertyTypeExtractor: $this->propertyTypeExtractor,
                typeResolver: $this->typeResolver,
                dynamicPropertiesDeterminer: $this->getDynamicPropertiesDeterminer(),
                attributesExtractor: $this->getAttributesExtractor(),
                unalterableDeterminer: $this->getUnalterableDeterminer(),
            );
    }

    private function getUnalterableDeterminer(): UnalterableDeterminer
    {
        return $this->unalterableDeterminer
            ??= new UnalterableDeterminer(
                propertyListExtractor: $this->propertyListExtractor,
                propertyAccessInfoExtractor: $this->getPropertyAccessInfoExtractor(),
                dynamicPropertiesDeterminer: $this->getDynamicPropertiesDeterminer(),
                attributesExtractor: $this->getAttributesExtractor(),
                propertyTypeExtractor: $this->propertyTypeExtractor,
            );
    }

    private function getClassMetadataFactory(): ClassMetadataFactory
    {
        return $this->classMetadataFactory
            ??= new ClassMetadataFactory(
                eagerPropertiesResolver: $this->eagerPropertiesResolver,
                dynamicPropertiesDeterminer: $this->getDynamicPropertiesDeterminer(),
                attributesExtractor: $this->getAttributesExtractor(),
                unalterableDeterminer: $this->getUnalterableDeterminer(),
            );
    }

    private function getPropertyMappingResolver(): PropertyMappingResolver
    {
        return $this->propertyMappingResolver
            ??= new PropertyMappingResolver(
                propertyListExtractor: $this->propertyListExtractor,
            );
    }

    public function getObjectToObjectMetadataFactory(): ObjectToObjectMetadataFactoryInterface
    {
        return $this->objectToObjectMetadataFactory
            ??= new ObjectToObjectMetadataFactory(
                propertyMapperResolver: $this->propertyMapperResolver,
                proxyFactory: $this->proxyFactory,
                propertyMetadataFactory: $this->getPropertyMetadataFactory(),
                classMetadataFactory: $this->getClassMetadataFactory(),
                propertyMappingResolver: $this->getPropertyMappingResolver(),
            );
    }
}
