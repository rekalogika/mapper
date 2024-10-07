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
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use Rekalogika\Mapper\MapperInterface;

/**
 * @implements Collector<Node\Expr\MethodCall,list<array{class-string|false,class-string|false,int}>>
 */
final class MapperCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        $fileName = $scope->getFile();
        $line = $node->getLine();

        // ensure method name is identifier
        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        // check if the method name is 'map'
        if ($node->name->toString() !== 'map') {
            return null;
        }

        // check if the type of the variable is MapperInterface
        if (!$scope->getType($node->var)->isSuperTypeOf(new ObjectType(MapperInterface::class))->yes()) {
            return null;
        }

        // get first argument
        $firstArg = $node->args[0];

        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        // get first arg type
        $firstArgType = $scope->getType($firstArg->value);

        // get first arg classnames
        $firstArgClassNames = $firstArgType->getObjectClassNames();

        // if not found set to false
        if ($firstArgClassNames === []) {
            $firstArgClassNames = [false];
        }

        // get second arg
        $secondArg = $node->args[1];

        if (!$secondArg instanceof Node\Arg) {
            return null;
        }

        // get second arg class names
        $secondArgType = $scope->getType($secondArg->value);
        $objectType = $secondArgType->getObjectTypeOrClassStringObjectType();
        $secondArgClassNames = $objectType->getObjectClassNames();

        // if not found set to false
        if ($secondArgClassNames === []) {
            $secondArgClassNames = [false];
        }

        $result = [];

        foreach ($firstArgClassNames as $firstArgClassName) {
            foreach ($secondArgClassNames as $secondArgClassName) {
                /**
                 * @var class-string|false $firstArgClassName
                 * @var class-string|false $secondArgClassName
                 */
                $result[] = [$firstArgClassName, $secondArgClassName, $line];
            }
        }

        return $result;
    }
}
