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
final class PropertyPathAwarePropertyInfoExtractorException extends \LogicException implements ExceptionInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(string $message, string $class, string $propertyPath)
    {
        $message = \sprintf('%s, root class: "%s", property path: "%s"', $message, $class, $propertyPath);

        parent::__construct($message);
    }
}
