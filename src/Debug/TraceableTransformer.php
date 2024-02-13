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

namespace Rekalogika\Mapper\Debug;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MainTransformer\Model\DebugContext;
use Rekalogika\Mapper\MainTransformer\Model\Path;
use Rekalogika\Mapper\Transformer\AbstractTransformerDecorator;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class TraceableTransformer extends AbstractTransformerDecorator implements
    TransformerInterface,
    MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    private bool $withMainTransformerCalled = false;

    public function __construct(
        private TransformerInterface $decorated,
        private MapperDataCollector $dataCollector
    ) {
    }

    public function getDecorated(): TransformerInterface
    {
        return $this->decorated;
    }

    public function withMainTransformer(MainTransformerInterface $mainTransformer): static
    {
        if ($this->withMainTransformerCalled) {
            return $this;
        }

        $this->withMainTransformerCalled = true;

        if ($this->decorated instanceof MainTransformerAwareInterface) {
            $this->decorated = $this->decorated->withMainTransformer($mainTransformer);
        }

        return $this;
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        $path = $context(Path::class)?->getLast();

        if ($debugContext = $context(DebugContext::class)) {
            $possibleTargetTypes = $debugContext->getTargetTypes();
            $sourceTypeGuessed = $debugContext->isSourceTypeGuessed();
        } else {
            $possibleTargetTypes = null;
            $sourceTypeGuessed = false;
        }

        $traceData = new TraceData(
            path: $path,
            source: $source,
            existingTargetValue: $target,
            possibleTargetTypes: $possibleTargetTypes,
            selectedTargetType: $targetType,
            transformerClass: $this->decorated::class,
            sourceTypeGuessed: $sourceTypeGuessed
        );

        // add trace data to parent trace data
        if ($parentTraceData = $context(TraceData::class)) {
            $parentTraceData->addNestedTraceData($traceData);
            $context = $context->with($traceData);
        } else {
            // @phpstan-ignore-next-line
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[2];
            $traceData->setCaller(
                $caller['file'] ?? null,
                $caller['line'] ?? null,
                $caller['function'],
                $caller['class'] ?? null,
                $caller['type'] ?? null
            );

            // if we are the root transformer, add the trace data to the
            // context, and collect it
            $context = $context->with($traceData);
            $this->dataCollector->collectTraceData($traceData);
        }

        $start = microtime(true);
        /** @var mixed */
        $result = $this->decorated->transform($source, $target, $sourceType, $targetType, $context);
        $time = microtime(true) - $start;

        $traceData->finalizeTime($time);
        $traceData->finalizeResult($result);

        return $result;
    }

    public function getSupportedTransformation(): iterable
    {
        return $this->decorated->getSupportedTransformation();
    }
}
