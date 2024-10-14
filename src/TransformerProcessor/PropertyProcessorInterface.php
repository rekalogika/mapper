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

namespace Rekalogika\Mapper\TransformerProcessor;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;

/**
 * @internal
 */
interface PropertyProcessorInterface
{
    public function readSourcePropertyAndWriteTargetProperty(
        object $source,
        object $target,
        Context $context,
    ): object;

    /**
     * @param object|null $target Target is null if the transformation is for a
     * constructor argument
     * @return array{mixed,bool} The target value after transformation and whether the value differs from before transformation
     */
    public function transformValue(
        object $source,
        ?object $target,
        bool $mandatory,
        Context $context,
    ): mixed;

    /**
     * @throws UnableToWriteException
     */
    public function writeTargetProperty(
        object $target,
        mixed $value,
        Context $context,
        bool $silentOnError,
    ): object;
}
