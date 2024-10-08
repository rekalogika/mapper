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

use Rekalogika\Mapper\Cache\WarmableTransformerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MainTransformer\Model\DebugContext;
use Rekalogika\Mapper\MainTransformer\Model\Path;
use Rekalogika\Mapper\Transformer\AbstractTransformerDecorator;
use Rekalogika\Mapper\Transformer\Exception\RefuseToTransformException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 */
final class TraceableTransformer extends AbstractTransformerDecorator implements
    TransformerInterface,
    MainTransformerAwareInterface,
    WarmableTransformerInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private TransformerInterface $decorated,
        private MapperDataCollector $dataCollector,
        private Stopwatch $stopwatch,
    ) {}

    #[\Override]
    public function getDecorated(): TransformerInterface
    {
        return $this->decorated;
    }

    #[\Override]
    public function withMainTransformer(MainTransformerInterface $mainTransformer): static
    {
        if (!$this->decorated instanceof MainTransformerAwareInterface) {
            return $this;
        }

        $clone = clone $this;
        $clone->decorated = $this->decorated->withMainTransformer($mainTransformer);

        return $clone;
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        $path = $context(Path::class)?->getLast();

        if (($debugContext = $context(DebugContext::class)) !== null) {
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
            sourceTypeGuessed: $sourceTypeGuessed,
        );

        // add trace data to parent trace data
        if (($parentTraceData = $context(TraceData::class)) !== null) {
            $parentTraceData->addNestedTraceData($traceData);
            $context = $context->with($traceData);
        } else {
            // @phpstan-ignore ekinoBannedCode.function
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[2];
            $traceData->setCaller(
                $caller['file'] ?? null,
                $caller['line'] ?? null,
                $caller['function'],
                $caller['class'] ?? null,
                $caller['type'] ?? null,
            );

            $context = $context->with($traceData);
        }

        try {
            $id = substr(hash('xxh64', uniqid((string) mt_rand(), true)), 0, 6);
            $start = $this->stopwatch->start('transform()-' . $id, 'mapper');
            /** @var mixed */
            $result = $this->decorated->transform($source, $target, $sourceType, $targetType, $context);
            $end = $this->stopwatch->stop('transform()-' . $id);

            $time = $end->getDuration();

            $traceData->finalize($time, $result);

            if ($parentTraceData === null) {
                $this->dataCollector->collectTraceData($traceData);
            }

            return $result;
        } catch (RefuseToTransformException $e) {
            $traceData->refusedToTransform();

            throw $e;
        }
    }

    public function warmTransform(
        Type $sourceType,
        Type $targetType,
        Context $context,
    ): void {
        if (!$this->decorated instanceof WarmableTransformerInterface) {
            return;
        }

        $this->decorated->warmTransform($sourceType, $targetType, $context);
    }

    public function isWarmable(): bool
    {
        return $this->decorated instanceof WarmableTransformerInterface
            && $this->decorated->isWarmable();
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        return $this->decorated->getSupportedTransformation();
    }
}
