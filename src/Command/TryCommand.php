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

namespace Rekalogika\Mapper\Command;

use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'rekalogika:mapper:try', description: 'Gets the mapping result from a source and target type pair.')]
class TryCommand extends Command
{
    public function __construct(
        private TransformerRegistryInterface $transformerRegistry,
        private TypeResolverInterface $typeResolver
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'The source type')
            ->addArgument('target', InputArgument::REQUIRED, 'The target type')
            ->setHelp("The <info>%command.name%</info> displays the mapping result from a source type and a target type.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];

        //
        // source type
        //

        /** @var string */
        $sourceTypeString = $input->getArgument('source');
        $sourceType = TypeFactory::fromString($sourceTypeString);
        $sourceTypeStrings = $this->typeResolver
            ->getAcceptedTransformerInputTypeStrings($sourceType);

        $rows[] = ['Source type', $sourceTypeString];
        $rows[] = new TableSeparator();
        $rows[] = [
            'Transformer source types compatible with source',
            implode("\n", $sourceTypeStrings)
        ];

        //
        // target type
        //

        /** @var string */
        $targetTypeString = $input->getArgument('target');
        $targetType = TypeFactory::fromString($targetTypeString);
        $targetTypeStrings = $this->typeResolver
            ->getAcceptedTransformerOutputTypeStrings($targetType);

        $rows[] = new TableSeparator();
        $rows[] = ['Target type', $targetTypeString];
        $rows[] = new TableSeparator();
        $rows[] = [
            'Transformer target types compatible with target',
            implode("\n", $targetTypeStrings)
        ];

        //
        // render
        //

        $io->section('<info>Type Compatibility</info>');
        $table = new Table($output);
        $table->setHeaders(['Subject', 'Value']);
        $table->setStyle('box');
        $table->setRows($rows);
        $table->render();

        //
        // get applicable transformers
        //

        $rows = [];

        $searchResult = $this->transformerRegistry
            ->findBySourceAndTargetTypes([$sourceType], [$targetType]);

        foreach ($searchResult as $entry) {
            $rows[] = [
                $entry->getMappingOrder(),
                $entry->getTransformerServiceId(),
                $entry->getTransformer()::class,
                $this->typeResolver->getTypeString($entry->getSourceType()),
                $this->typeResolver->getTypeString($entry->getTargetType()),
                $entry->isVariantTargetType() ? 'variant' : 'invariant',
            ];
            $rows[] = new TableSeparator();
        }

        array_pop($rows);

        //
        // render
        //

        $io->writeln('');
        $io->section('<info>Applicable Transformers</info>');

        if (count($rows) === 0) {
            $io->error('No applicable transformers found.');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Ordering', 'Service ID', 'Class', 'Source Type', 'Target Type', 'Variance']);
        $table->setStyle('box');
        $table->setVertical();
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}
