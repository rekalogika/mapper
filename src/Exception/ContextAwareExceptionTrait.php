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

namespace Rekalogika\Mapper\Exception;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\Model\Path;

trait ContextAwareExceptionTrait
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected ?Context $context = null,
    ) {
        $path = $context?->get(Path::class);

        $path = (string) $path;
        if ($path === '') {
            $path = '(root)';
        }

        $message = sprintf('%s Mapping path: "%s".', $message, $path);

        if ($previous !== null) {
            $message = sprintf('%s Previous message: %s.', $message, $previous->getMessage());
        }

        parent::__construct($message, $code, $previous);
    }
}
