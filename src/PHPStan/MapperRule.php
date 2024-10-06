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

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use Rekalogika\Mapper\Cache\MappingCollection;

/**
 * @implements Rule<CollectedDataNode>
 */
final class MapperRule implements Rule
{
    public function __construct(private ?string $mapperDumpFile = null) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->mapperDumpFile === null) {
            return [];
        }

        $currentDirectory = getcwd();

        if ($currentDirectory === false) {
            throw new \RuntimeException('Cannot get current working directory');
        }

        // add slash at the end if not exists
        $currentDirectory = rtrim($currentDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $mapData = $node->get(MapperCollector::class);

        $result = [];

        foreach ($mapData as $file => $fileData) {
            foreach ($fileData as $invocationData) {
                foreach ($invocationData as [$source, $target, $line]) {
                    $key = $source . '-' . $target;

                    $strippedFile = str_replace($currentDirectory, '', $file);

                    $result[$key]['source'] = $source;
                    $result[$key]['target'] = $target;
                    $result[$key]['occurences'][$strippedFile][] = $line;
                }
            }
        }

        $this->createConfig($result);

        return [];
    }

    /**
     * @param array<string,array{source:class-string,target:class-string,occurences:array<string,list<int>>}> $data
     */
    private function createConfig(array $data): void
    {
        $configPath = $this->mapperDumpFile;

        if ($configPath === null) {
            return;
        }

        ksort($data);

        $stmts = [];

        foreach ($data as $record) {
            $source = $record['source'];
            $target = $record['target'];
            $occurences = $record['occurences'];
            ksort($occurences);

            $comments = [];

            foreach ($occurences as $file => $lines) {
                sort($lines);

                if (\count($lines) > 1) {
                    $commentText = \sprintf('// %s on lines: %s', $file, implode(', ', $lines));
                } else {
                    $commentText = \sprintf('// %s on line: %s', $file, $lines[0]);
                }

                $comments[] = new Comment($commentText);
            }

            $stmts[] = new Expression(
                attributes: ['comments' => [new Comment('')]],
                expr: new MethodCall(
                    var: new Variable(name: 'mappingCollection'),
                    name: new Identifier(name: 'addObjectMapping'),
                    args: [
                        new Arg(
                            attributes: ['comments' => $comments],
                            name: new Identifier('source'),
                            value: new ClassConstFetch(
                                class: new FullyQualified(name: $source),
                                name: 'class',
                            ),
                        ),
                        new Arg(
                            // attributes: ['comments' => [new Comment("\n")]],
                            name: new Identifier('target'),
                            value: new ClassConstFetch(
                                class: new FullyQualified(name: $target),
                                name: 'class',
                            ),
                        ),
                    ],
                ),
            );
        }

        $closure = new Closure(
            subNodes: [
                'stmts' => $stmts,
                'returnType' => new Identifier('void'),
                'params' => [
                    new Param(
                        type: new Name('MappingCollection'),
                        var: new Variable('mappingCollection'),
                    ),
                ],
            ],
        );

        $return = new Return_(
            attributes: ['comments' => [new Comment('')]],
            expr: $closure,
        );

        $nodes = [];

        $nodes[] = new Use_(
            attributes: ['comments' => [
                new Comment('// This file is automatically generated by the rekalogika/mapper package.'),
                new Comment('// Any changes made to this file will be lost when the file is regenerated.'),
                new Comment('// You may add additional mappings in a separate file alongside this file.'),
                new Comment(''),
            ]],
            uses: [
                new UseUse(
                    name: new Name(MappingCollection::class),
                ),
            ],
        );

        $nodes[] = $return;

        $prettyPrinter = new Standard();
        $code = $prettyPrinter->prettyPrintFile($nodes);

        file_put_contents($configPath, $code);
    }
}
