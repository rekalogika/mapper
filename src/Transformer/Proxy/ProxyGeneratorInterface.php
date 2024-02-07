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

namespace Rekalogika\Mapper\Transformer\Proxy;

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\Proxy\Exception\ProxyNotSupportedException;

interface ProxyGeneratorInterface
{
    /**
     * @throws ProxyNotSupportedException
     */
    public function generateTargetProxy(
        ObjectToObjectMetadata $metadata
    ): ProxySpecification;
}
