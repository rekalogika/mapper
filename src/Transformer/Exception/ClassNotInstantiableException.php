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

class ClassNotInstantiableException extends NotMappableValueException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, Context $context = null)
    {
        parent::__construct(
            message: \sprintf('Trying to instantiate "%s", but it is not instantiable. You might solve this problem by adding an "InheritanceMap" to the class, so the mapper will know which concrete class to instantiate.', $class),
            context: $context,
        );
    }
}
