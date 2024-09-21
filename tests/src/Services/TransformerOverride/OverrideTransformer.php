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

namespace Rekalogika\Mapper\Tests\Services\TransformerOverride;

use PhpParser\Node\Scalar;
use Rekalogika\Mapper\Tests\Fixtures\Override\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\Override\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Transformer\AbstractTransformerDecorator;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Forcing scalar to scalar transformer, erroneously
 */
class OverrideTransformer extends AbstractTransformerDecorator
{
    public function __construct(
        #[Autowire(service: ScalarToScalarTransformer::class)]
        TransformerInterface $transformer,
    ) {
        parent::__construct($transformer);
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::objectOfClass(ObjectWithArrayProperty::class),
            TypeFactory::objectOfClass(ObjectWithArrayPropertyDto::class),
        );
    }
}
