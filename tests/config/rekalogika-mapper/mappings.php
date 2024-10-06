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

use Rekalogika\Mapper\Cache\MappingCollection;
use Rekalogika\Mapper\Tests\Fixtures\Basic\Person;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonDto;

return static function (MappingCollection $mappingCollection): void {
    $mappingCollection
        ->addObjectMapping(Person::class, PersonDto::class)
        ->addObjectMapping(PersonDto::class, Person::class)
    ;
};
