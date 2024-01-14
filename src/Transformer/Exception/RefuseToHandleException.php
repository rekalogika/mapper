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

use Rekalogika\Mapper\Exception\RuntimeException;

/**
 * If a transformer throw this exception, it means that the transformer is not
 * able to handle the given source type in an ad-hoc basis. The main transformer
 * will try the next transformer for the task.
 */
class RefuseToHandleException extends RuntimeException
{
}
