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

use Rekalogika\Mapper\Context\Context;

class ClassNotInInheritanceMapException extends NotMappableValueException
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function __construct(
        string $sourceClass,
        string $targetClass,
        Context $context = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Trying to map source class "%s" to target class "%s" using an inheritance map, but the target class "%s" is missing from the inheritance map.',
                $sourceClass,
                $targetClass,
                $targetClass
            ),
            context: $context,
        );
    }
}
