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

namespace Rekalogika\Mapper\CustomMapper\Exception;

use Rekalogika\Mapper\Exception\UnexpectedValueException;

/**
 * @internal
 */
final class ObjectMapperNotFoundException extends UnexpectedValueException
{
    public function __construct(
        string $sourceClass,
        string $targetClass,
    ) {
        parent::__construct(\sprintf(
            'Object mapper not found for source class "%s" and target class "%s".',
            $sourceClass,
            $targetClass,
        ));
    }
}
