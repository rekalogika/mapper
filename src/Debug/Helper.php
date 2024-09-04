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

namespace Rekalogika\Mapper\Debug;

use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

final readonly class Helper
{
    /**
     * @param array<int,MixedType|Type>|Type $type
     */
    public function typeToHtml(null|array|MixedType|Type $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        return TypeUtil::getTypeStringHtml($type);
    }
}
