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

use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: 'rekalogika:mapper:mapping', description: 'Dump mapping table')]
final class MappingCommand extends Command
{
    public function __construct(
        private readonly MappingFactoryInterface $mappingFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Filter by source type')
            ->addOption('target', 't', InputOption::VALUE_OPTIONAL, 'Filter by target type')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Filter by class name or service ID')
            ->setHelp("The <info>%command.name%</info> command dumps the mapping table for the mapper.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null */
        $sourceOption = $input->getOption('source');
        /** @var string|null */
        $targetOption = $input->getOption('target');
        /** @var string|null */
        $classOption = $input->getOption('class');

        $io = new SymfonyStyle($input, $output);
        $title = '<info>Mapping Table</info>';
        $rows = [];

        $mapping = $this->mappingFactory->getMapping();

        foreach ($mapping as $entry) {
            $order = $entry->getOrder();
            $sourceType = $entry->getSourceTypeString();
            $targetType = $entry->getTargetTypeString();
            $class = $entry->getClass();
            $id = $entry->getId();
            $variantTargetType = $entry->isVariantTargetType();

            if ($sourceOption !== null) {
                if (preg_match('/' . preg_quote($sourceOption) . '/i', $entry->getSourceTypeString()) === 0) {
                    continue;
                }

                $sourceType = preg_replace('/(' . preg_quote($sourceOption) . ')/i', '<bg=yellow>$1</>', $sourceType);
            }

            if ($targetOption !== null) {
                if (preg_match('/' . preg_quote($targetOption) . '/i', $entry->getTargetTypeString()) === 0) {
                    continue;
                }

                $targetType = preg_replace('/(' . preg_quote($targetOption) . ')/i', '<bg=yellow>$1</>', $targetType);
            }

            if ($classOption !== null) {
                if (
                    preg_match('/' . preg_quote($classOption) . '/i', $entry->getClass()) === 0
                    && preg_match('/' . preg_quote($classOption) . '/i', $entry->getId()) === 0
                ) {
                    continue;
                }

                // $class = preg_replace('/(' . preg_quote($classOption) . ')/i', '<bg=yellow>$1</>', $class);
                $id = preg_replace('/(' . preg_quote($classOption) . ')/i', '<bg=yellow>$1</>', $id);
            }

            $rows[] = [
                $order,
                $sourceType,
                $targetType,
                $variantTargetType ? 'variant' : 'invariant',
                $id,
                $class,
            ];
        }

        $io->section($title);
        $table = new Table($output);
        $table->setHeaders([
            'Ordering',
            'Source Type',
            'Target Type',
            'Target Variance',
            'Service ID',
            'Class'
        ]);
        $table->setStyle(new MarkdownLikeTableStyle());
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}
