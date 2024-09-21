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

namespace Rekalogika\Mapper\Tests\Services\ObjectMapper;

use Rekalogika\Mapper\Attribute\AsObjectMapper;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\FinalPersonDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Person;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\PersonDto;

class PersonToPersonDtoMapper
{
    #[AsObjectMapper]
    public function mapPersonToPersonDto(
        Person $person,
        SubMapperInterface $subMapper,
    ): PersonDto {
        $initializer = static function (
            PersonDto $proxy,
        ) use ($person): void {
            $proxy->name = $person->getName();
        };

        $personDto = $subMapper->createProxy(
            PersonDto::class,
            $initializer,
            ['id'],
        );

        $personDto->id = $person->getId();

        return $personDto;
    }

    #[AsObjectMapper]
    public function mapPersonToFinalPersonDto(
        Person $person,
        SubMapperInterface $subMapper,
    ): FinalPersonDto {
        $personDto = $subMapper->createProxy(
            FinalPersonDto::class,
            static function (FinalPersonDto $proxy) use ($person): void {
                $proxy->name = $person->getName();
            },
            ['id'],
        );

        $personDto->id = $person->getId();

        return $personDto;
    }
}
