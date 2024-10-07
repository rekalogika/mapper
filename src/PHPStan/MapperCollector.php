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

namespace Rekalogika\Mapper\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use Rekalogika\Mapper\IterableMapperInterface;
use Rekalogika\Mapper\MapperInterface;

/**
 * @todo collect from mapiterable
 * @implements Collector<Node\Expr\MethodCall,list<array{class-string|false,class-string|false,int}>>
 */
final class MapperCollector implements Collector
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        // $fileName = $scope->getFile();
        $line = $node->getLine();

        // ensure method name is identifier
        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        // check if the variable is an instance of MapperInterface & the method
        // name is 'map'
        if (
            $scope->getType($node->var)->isSuperTypeOf(new ObjectType(MapperInterface::class))->yes()
            && $node->name->toString() === 'map'
        ) {
            [$sourceClassNames, $targetClassNames] =
                $this->processMapperInterfaceMap($node, $scope);
        } elseif (
            $scope->getType($node->var)->isSuperTypeOf(new ObjectType(IterableMapperInterface::class))->yes()
            && $node->name->toString() === 'mapIterable'
        ) {
            [$sourceClassNames, $targetClassNames] =
                $this->processIterableMapperInterfaceMapIterable($node, $scope);
        } else {
            return null;
        }

        // if not found set to false
        if ($sourceClassNames === []) {
            $sourceClassNames = [false];
        }

        if ($targetClassNames === []) {
            $targetClassNames = [false];
        }

        $result = [];

        foreach ($sourceClassNames as $firstArgClassName) {
            foreach ($targetClassNames as $secondArgClassName) {
                /**
                 * @var class-string|false $firstArgClassName
                 * @var class-string|false $secondArgClassName
                 */
                $result[] = [$firstArgClassName, $secondArgClassName, $line];
            }
        }

        return $result;
    }

    /**
     * @return array{list<class-string>,list<class-string>}
     */
    private function processMapperInterfaceMap(MethodCall $node, Scope $scope): array
    {
        // get first argument
        $firstArg = $node->args[0];

        if (!$firstArg instanceof Arg) {
            $sourceClassNames = [];
        } else {
            $firstArgType = $scope->getType($firstArg->value);
            $sourceClassNames = $firstArgType->getObjectClassNames();
        }

        // get second arg
        $secondArg = $node->args[1];

        if (!$secondArg instanceof Arg) {
            $targetClassNames = [];
        } else {
            $secondArgType = $scope->getType($secondArg->value);
            $objectType = $secondArgType->getObjectTypeOrClassStringObjectType();
            $targetClassNames = $objectType->getObjectClassNames();
        }

        /**
         * @var list<class-string> $sourceClassNames
         * @var list<class-string> $targetClassNames
         */

        return [$sourceClassNames, $targetClassNames];
    }

    /**
     * @return array{list<class-string>,list<class-string>}
     */
    private function processIterableMapperInterfaceMapIterable(
        MethodCall $node,
        Scope $scope
    ): array {
        // get first argument
        $firstArg = $node->args[0];

        if (!$firstArg instanceof Arg) {
            $sourceClassNames = [];
        } else {
            $firstArgType = $scope->getType($firstArg->value);

            if (!$firstArgType->isIterable()->yes()) {
                $sourceClassNames = [];
            } else {
                $itemType = $firstArgType->getIterableValueType();
                $sourceClassNames = $itemType->getObjectClassNames();
            }
        }

        // get second arg
        $secondArg = $node->args[1];

        if (!$secondArg instanceof Arg) {
            $targetClassNames = [];
        } else {
            $secondArgType = $scope->getType($secondArg->value);
            $objectType = $secondArgType->getObjectTypeOrClassStringObjectType();
            $targetClassNames = $objectType->getObjectClassNames();
        }

        /**
         * @var list<class-string> $sourceClassNames
         * @var list<class-string> $targetClassNames
         */

        return [$sourceClassNames, $targetClassNames];
    }
}
