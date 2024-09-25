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

namespace Rekalogika\Mapper\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final readonly class DateTimeOptions
{
    /**
     * @param string|null $stringFormat The string format for
     * DateTimeInterface::format()
     * @param \DateTimeZone|non-empty-string|null $timeZone If specified, the DateTime will be
     * converted to the specified time zone.
     */
    public function __construct(
        private ?string $stringFormat = null,
        private null|string|\DateTimeZone $timeZone = null,
    ) {}

    public function getStringFormat(): ?string
    {
        return $this->stringFormat;
    }

    public function getTimeZone(): null|\DateTimeZone
    {
        if ($this->timeZone === null) {
            return null;
        }

        if (is_string($this->timeZone)) {
            return new \DateTimeZone($this->timeZone);
        }

        return $this->timeZone;
    }
}
