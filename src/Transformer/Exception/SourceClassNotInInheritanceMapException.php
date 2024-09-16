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

namespace Rekalogika\Mapper\Transformer\Exception;

use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Context\Context;

class SourceClassNotInInheritanceMapException extends NotMappableValueException
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function __construct(
        string $sourceClass,
        string $targetClass,
        ?Context $context = null,
    ) {
        parent::__construct(
            message: \sprintf(
                'Trying to map to a class with an inheritance map, but source class "%s" is not found in the "%s" attribute of the target class "%s".',
                $sourceClass,
                InheritanceMap::class,
                $targetClass,
            ),
            context: $context,
        );
    }
}
