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

namespace Rekalogika\Mapper;

use Rekalogika\Mapper\Exception\CircularReferenceException;
use Rekalogika\Mapper\Exception\ExceptionInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\LogicException;

interface MapperInterface
{
    /**
     * @template T of object
     * @param class-string<T>|T|"int"|"string"|"float"|"bool"|"array" $target
     * @param array<string,mixed> $context
     * @return ($target is class-string<T>|T ? T : ($target is "int" ? int : ($target is "string" ? string : ($target is "float" ? float : ($target is "bool" ? bool : ($target is "array" ? array<array-key,mixed> : mixed ))))))
     * @throws InvalidArgumentException
     * @throws CircularReferenceException
     * @throws LogicException
     * @throws ExceptionInterface
     */
    public function map(mixed $source, mixed $target, array $context = []): mixed;
}
