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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\TransformerOverride\OverrideTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;

class TransformerOverrideTest extends AbstractIntegrationTest
{
    protected function getAdditionalTransformers(): array
    {
        $scalarToScalarTransformer = new ScalarToScalarTransformer();

        return [
            OverrideTransformer::class => new OverrideTransformer($scalarToScalarTransformer),
        ];
    }

    public function testTransformerOverride(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be scalar');
        $source = new ObjectWithArrayProperty();
        $target = $this->mapper->map($source, ObjectWithArrayPropertyDto::class);
    }
}
