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

namespace Rekalogika\Mapper\Transformer;

use Rekalogika\Mapper\Context\Context;
use Symfony\Component\PropertyInfo\Type;

abstract class AbstractTransformerDecorator implements TransformerInterface
{
    public function __construct(
        private readonly TransformerInterface $decorated,
    ) {}

    public function getDecorated(): TransformerInterface
    {
        return $this->decorated;
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        return $this->decorated->transform(
            $source,
            $target,
            $sourceType,
            $targetType,
            $context,
        );
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        return $this->decorated->getSupportedTransformation();
    }
}
