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

namespace Rekalogika\Mapper\Transformer\Contracts;

use Rekalogika\Mapper\Exception\CircularReferenceException;
use Rekalogika\Mapper\Exception\ExceptionInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\LogicException;
use Symfony\Component\PropertyInfo\Type;

interface TransformerInterface
{
    public const MIXED = 'mixed';

    /**
     * @param array<string,mixed> $context
     *
     * @throws InvalidArgumentException
     * @throws CircularReferenceException
     * @throws LogicException
     * @throws ExceptionInterface
     */
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        array $context
    ): mixed;

    /**
     * @return iterable<int,TypeMapping>
     */
    public function getSupportedTransformation(): iterable;
}
