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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\EntityWithMultipleIdentifier;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\EntityWithSingleIdentifier;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\EntityWithSingleIdentifierDto;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\SimpleEntity;
use Rekalogika\Mapper\Tests\Fixtures\Doctrine\SimpleEntityInputDto;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\DoctrineEagerPropertiesResolver;

class DoctrineTest extends FrameworkTestCase
{
    public function testDoctrine(): void
    {
        $em = $this->getEntityManager();
        $entity = new EntityWithSingleIdentifier('my-identifier', 'my-name');
        $em->persist($entity);
        $em->flush();

        $em->clear();

        $entity = $em->find(EntityWithSingleIdentifier::class, 'my-identifier');
        $this->assertNotNull($entity);
        $this->assertSame('my-identifier', $entity->getMyIdentifier());
        $this->assertSame('my-name', $entity->getName());
    }

    public function testDoctrineEagerPropertiesResolver(): void
    {
        $eagerPropertiesResolver = $this->get('rekalogika.mapper.eager_properties_resolver.doctrine');
        $this->assertInstanceOf(DoctrineEagerPropertiesResolver::class, $eagerPropertiesResolver);

        $eagerProperties = $eagerPropertiesResolver->getEagerProperties(EntityWithSingleIdentifier::class);
        $this->assertEquals(['myIdentifier'], $eagerProperties);
    }

    public function testEagerPropertiesResolver(): void
    {
        $eagerPropertiesResolver = $this->get('rekalogika.mapper.eager_properties_resolver');
        $this->assertInstanceOf(EagerPropertiesResolverInterface::class, $eagerPropertiesResolver);

        $eagerProperties = $eagerPropertiesResolver->getEagerProperties(EntityWithSingleIdentifier::class);
        $this->assertEquals(['myIdentifier'], $eagerProperties);
    }

    public function testLazy(): void
    {
        $a = new EntityWithSingleIdentifier('a', 'a');
        $b = new EntityWithSingleIdentifier('b', 'b');
        $c = new EntityWithSingleIdentifier('c', 'c');
        $a->addChild($b);
        $b->addChild($c);

        $em = $this->getEntityManager();

        $em->persist($a);
        $em->persist($b);
        $em->persist($c);
        $em->flush();

        $em->clear();

        $b = $em->find(EntityWithSingleIdentifier::class, 'b');
        $this->assertNotNull($b);

        // accessing identifier does not trigger a full load
        $bdto = $this->mapper->map($b, EntityWithSingleIdentifierDto::class);
        $this->assertIsUninitializedProxy($bdto);
        $foo = $bdto->myIdentifier;
        $this->assertIsUninitializedProxy($bdto);

        // accessing identifier does not trigger a full load
        $adto = $bdto->parent;
        $this->assertNotNull($adto);
        $this->assertIsUninitializedProxy($adto);
        $foo = $adto->myIdentifier;
        $this->assertIsUninitializedProxy($adto);

        // accessing non identifier triggers a full load
        $foo = $adto->name;
        $this->assertNotUninitializedProxy($adto);
    }

    public function testCompositeId(): void
    {
        $eagerPropertiesResolver = $this->get('rekalogika.mapper.eager_properties_resolver');
        $this->assertInstanceOf(EagerPropertiesResolverInterface::class, $eagerPropertiesResolver);

        $eagerProperties = $eagerPropertiesResolver->getEagerProperties(EntityWithMultipleIdentifier::class);
        $this->assertEquals(['id1', 'id2'], $eagerProperties);
    }

    public function testInputDtoToEntityMapping(): void
    {
        $input = new SimpleEntityInputDto();
        $input->name = 'my-name';

        $entity = $this->mapper->map($input, SimpleEntity::class);
        $this->assertNotUninitializedProxy($entity);
    }

}
