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

use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;

/**
 * @internal
 */
interface ObjectProcessorFactoryInterface extends MainTransformerAwareInterface
{
    public function getObjectProcessor(
        ObjectToObjectMetadata $metadata,
    ): ObjectProcessorInterface;
}
