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

namespace Rekalogika\Mapper\Transformer\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

/**
 * Do object to array, array to object, and array to array transformation by
 * converting array to stdClass & passing the task to ObjectToObjectTransformer.
 */
final readonly class ArrayObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    public function __construct(
        private ObjectToObjectTransformer $objectToObjectTransformer,
    ) {}

    #[\Override]
    public function withMainTransformer(MainTransformerInterface $mainTransformer): static
    {
        $objectToObjectTransformer = $this->objectToObjectTransformer
            ->withMainTransformer($mainTransformer)
        ;

        return new self($objectToObjectTransformer);
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        $originalTargetType = $targetType;

        if (TypeCheck::isArray($sourceType)) {
            $source = (object) $source;
            $sourceType = TypeFactory::objectOfClass(\stdClass::class);
        }

        if (TypeCheck::isArray($targetType)) {
            $target = (object) $target;
            $targetType = TypeFactory::objectOfClass(\stdClass::class);
        }

        /** @var mixed */
        $result = $this->objectToObjectTransformer->transform(
            source: $source,
            target: $target,
            sourceType: $sourceType,
            targetType: $targetType,
            context: $context
        );

        if (TypeCheck::isArray($originalTargetType)) {
            return (array) $result;
        }

        return $result;
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::array(), TypeFactory::object(), true);

        yield new TypeMapping(TypeFactory::object(), TypeFactory::array());

        yield new TypeMapping(TypeFactory::array(), TypeFactory::array());
    }
}
