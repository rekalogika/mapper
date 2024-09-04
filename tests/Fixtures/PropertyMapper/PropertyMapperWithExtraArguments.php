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

namespace Rekalogika\Mapper\Tests\Fixtures\PropertyMapper;

use Rekalogika\Mapper\Attribute\AsPropertyMapper;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;

#[AsPropertyMapper(targetClass: SomeObjectDto::class)]
class PropertyMapperWithExtraArguments
{
    #[AsPropertyMapper]
    public function mapPropertyE(
        SomeObject $object,
        Context $context,
        MainTransformerInterface $mainTransformer,
    ): string {
        return \sprintf(
            'I have "%s" and "%s" that I can use to transform source property "%s"',
            $context::class,
            $mainTransformer::class,
            $object::class,
        );
    }
}
