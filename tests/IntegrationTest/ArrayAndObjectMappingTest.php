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

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class ArrayAndObjectMappingTest extends AbstractIntegrationTest
{
    public function testArrayToObject(): void
    {
        $array = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
        ];
        $dto = $this->mapper->map($array, ObjectWithScalarPropertiesDto::class);

        $this->assertEquals(1, $dto->a);
        $this->assertEquals('string', $dto->b);
        $this->assertEquals(true, $dto->c);
        $this->assertEquals(1.1, $dto->d);
    }

    // public function testObjectToArray(): void
    // {
    //     $class = new ObjectWithScalarProperties();

    //     $array = $this->mapper->map($class, 'array');

    //     $this->assertEquals(1, $array['a'] ?? null);
    //     $this->assertEquals('string', $array['b'] ?? null);
    //     $this->assertEquals(true, $array['c'] ?? null);
    //     $this->assertEquals(1.1, $array['d'] ?? null);
    // }

}
