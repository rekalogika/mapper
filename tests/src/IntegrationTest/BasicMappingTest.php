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

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Basic\Dog;
use Rekalogika\Mapper\Tests\Fixtures\Basic\DogDto;
use Rekalogika\Mapper\Tests\Fixtures\Basic\ImmutableDog;
use Rekalogika\Mapper\Tests\Fixtures\Basic\Person;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonDto;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonWithDog;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonWithDogDto;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonWithImmutableDogWithoutSetter;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonWithoutAge;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonWithoutAgeDto;

class BasicMappingTest extends FrameworkTestCase
{
    public function testBasicMapping(): void
    {
        $source = new Person('John Doe', 30);
        $target = $this->mapper->map($source, PersonDto::class);

        $this->assertSame('John Doe', $target->name);
        $this->assertSame(30, $target->age);
    }

    public function testMissingTargetProperty(): void
    {
        $source = new Person('John Doe', 30);
        $target = $this->mapper->map($source, PersonWithoutAgeDto::class);

        $this->assertSame('John Doe', $target->name);
    }

    public function testMissingSourceProperty(): void
    {
        $source = new PersonWithoutAge('John Doe');
        $target = $this->mapper->map($source, PersonDto::class);

        $this->assertSame('John Doe', $target->name);
        $this->assertNull($target->age);
    }

    public function testSetterNotCalledIfValueDoesntChange(): void
    {
        // source
        $personDto = new PersonWithDogDto();
        $dogDto = new DogDto();
        $dogDto->name = 'Hoop';
        $personDto->dog = $dogDto;
        $personDto->name = 'John';

        // target
        $dog = new Dog('Rex');
        $person = new PersonWithDog('Jon', $dog);

        $this->assertFalse($person->dogSetterCalled);
        $this->assertEquals('Jon', $person->getName());
        $this->assertEquals('Rex', $person->getDog()?->getName());


        $this->mapper->map($personDto, $person);

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        $this->assertFalse($person->dogSetterCalled);
        $this->assertEquals('John', $person->getName());
        $this->assertEquals('Hoop', $person->getDog()?->getName());
    }

    public function testSkippingImmutableEntityWithNoSetterOnTarget(): void
    {
        // source
        $personDto = new PersonWithDogDto();
        $dogDto = new DogDto();
        $dogDto->name = 'Hoop';
        $personDto->dog = $dogDto;
        $personDto->name = 'John';

        // target
        $dog = new ImmutableDog('Rex');
        $person = new PersonWithImmutableDogWithoutSetter('Jon', $dog);

        $this->assertEquals('Jon', $person->getName());
        $this->assertEquals('Rex', $person->getDog()->getName());


        $this->mapper->map($personDto, $person);

        $this->assertEquals('John', $person->getName());
        $this->assertEquals('Rex', $person->getDog()->getName());
    }
}
