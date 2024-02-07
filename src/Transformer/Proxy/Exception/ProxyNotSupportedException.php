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

namespace Rekalogika\Mapper\Transformer\Proxy\Exception;

use Rekalogika\Mapper\Exception\RuntimeException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;

class ProxyNotSupportedException extends RuntimeException
{
    public function __construct(ObjectToObjectMetadata $metadata, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Creating target proxy is not supported for target class "%s" and source class "%s"',
                $metadata->getTargetClass(),
                $metadata->getSourceClass()
            ),
            previous: $previous
        );
    }
}
