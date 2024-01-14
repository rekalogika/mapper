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
use Rekalogika\Mapper\Context\ContextMemberNotFoundException;
use Rekalogika\Mapper\MainTransformer\Model\Path;

trait ContextAwareExceptionTrait
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected ?Context $context = null,
    ) {
        try {
            $path = $context?->get(Path::class);
        } catch (ContextMemberNotFoundException) {
            $path = null;
        }

        if ($path !== null) {
            $path = (string) $path;
            if ($path === '') {
                $path = '(root)';
            }
            parent::__construct(sprintf('%s Mapping path: "%s".', $message, $path), $code, $previous);
        } else {
            parent::__construct($message, $code, $previous);
        }
    }
}
