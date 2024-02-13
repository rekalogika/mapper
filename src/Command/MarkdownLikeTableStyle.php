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

namespace Rekalogika\Mapper\Command;

use Symfony\Component\Console\Helper\TableStyle;

/**
 * Markdown-like table style, for the ease of copy-paste to documentation.
 */
final class MarkdownLikeTableStyle extends TableStyle
{
    public function __construct()
    {
        $this->setDefaultCrossingChar('|');
    }
}
