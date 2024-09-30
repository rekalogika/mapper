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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\Model;

use Rekalogika\Mapper\Transformer\Context\AttributesTrait;

/**
 * @implements \IteratorAggregate<object>
 * @internal
 */
final readonly class Attributes implements \IteratorAggregate
{
    use AttributesTrait;
}
