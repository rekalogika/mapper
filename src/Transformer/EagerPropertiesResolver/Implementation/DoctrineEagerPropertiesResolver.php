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

namespace Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;

/**
 * @internal
 */
final readonly class DoctrineEagerPropertiesResolver implements EagerPropertiesResolverInterface
{
    public function __construct(private ManagerRegistry $managerRegistry) {}

    #[\Override]
    public function getEagerProperties(string $sourceClass): array
    {
        $manager = $this->managerRegistry->getManagerForClass($sourceClass);

        if (!$manager) {
            return [];
        }

        try {
            $metadata = $manager->getClassMetadata($sourceClass);
        } catch (\ReflectionException | MappingException) {
            return [];
        }

        return array_values($metadata->getIdentifierFieldNames());
    }
}
