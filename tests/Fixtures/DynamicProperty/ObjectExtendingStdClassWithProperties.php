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

namespace Rekalogika\Mapper\Tests\Fixtures\DynamicProperty;

class ObjectExtendingStdClassWithProperties extends \stdClass
{
    public ?string $public = null;

    private ?string $private = null;

    public function __construct(private readonly ?string $constructor = null)
    {
    }

    public function getConstructor(): ?string
    {
        return $this->constructor;
    }

    public function getPrivate(): ?string
    {
        return $this->private;
    }
}
