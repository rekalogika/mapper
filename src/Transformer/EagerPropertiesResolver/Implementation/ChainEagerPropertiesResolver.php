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

use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;

/**
 * @internal
 */
final readonly class ChainEagerPropertiesResolver implements EagerPropertiesResolverInterface
{
    /**
     * @param iterable<EagerPropertiesResolverInterface> $resolvers
     */
    public function __construct(private iterable $resolvers)
    {
    }

    public function getEagerProperties(string $sourceClass): array
    {
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->getEagerProperties($sourceClass);
            if (!empty($result)) {
                return $result;
            }
        }

        return [];
    }
}
