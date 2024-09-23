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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata;

/**
 * @internal
 */
enum WriteMode
{
    case None;
    case Method;
    case Property;
    case AdderRemover;
    case Constructor;
    case DynamicProperty;
    case PropertyPath;
}
