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

use Rekalogika\Mapper\Exception\ExceptionInterface;

/**
 * @internal
 */
class PairedPropertyNotFoundException extends \LogicException implements ExceptionInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        string $property,
        string $pairedClass,
        string $pairedProperty,
    ) {
        $message = \sprintf(
            'Trying to map class "%s" property "%s" to class "%s" property "%s" according to the "Map" attribute, but the property "%s" is not found in class "%s"',
            $class,
            $property,
            $pairedClass,
            $pairedProperty,
            $pairedProperty,
            $pairedClass,
        );

        parent::__construct($message);
    }
}
