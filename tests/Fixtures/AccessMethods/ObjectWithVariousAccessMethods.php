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

namespace Rekalogika\Mapper\Tests\Fixtures\AccessMethods;

class ObjectWithVariousAccessMethods
{
    private string $privatePropertyWithGetterSetter = 'privateProperty';
    // @phpstan-ignore-next-line
    private string $privatePropertyWithoutGetterSetter = 'privatePropertyWithoutGetterSetter';
    public string $publicPropertyWithGetterSetter = 'publicProperty';
    public string $publicPropertyWithoutGetterSetter = 'publicPropertyWithoutGetterSetter';


    public bool $publicPropertySetterAccessed = false;
    public bool $publicPropertyGetterAccessed = false;

    public string $unsetPublicProperty;

    // @phpstan-ignore-next-line
    private string $unsetPrivatePropertyWithGetter;

    public function getPrivateProperty(): string
    {
        return $this->privatePropertyWithGetterSetter;
    }

    public function setPrivateProperty(string $privateProperty): void
    {
        $this->privatePropertyWithGetterSetter = $privateProperty;
    }

    public function getPublicProperty(): string
    {
        $this->publicPropertyGetterAccessed = true;
        return $this->publicPropertyWithGetterSetter;
    }

    public function setPublicProperty(string $publicProperty): void
    {
        $this->publicPropertySetterAccessed = true;
        $this->publicPropertyWithGetterSetter = $publicProperty;
    }

    public function getUnsetPrivatePropertyWithGetter(): string
    {
        return $this->unsetPrivatePropertyWithGetter;
    }
}
