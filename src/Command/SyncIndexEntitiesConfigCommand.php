<?php

namespace Nramos\SearchIndexer\Command;

use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;
use Nramos\SearchIndexer\Indexer\IndexableObjects;
use Nramos\SearchIndexer\Tests\Command\IndexEntitiesCommandTest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see IndexEntitiesCommandTest
 */
#[AsCommand(
    name: 'search:sync-config',
    description: 'sync config for search index',
)]
class SyncIndexEntitiesConfigCommand extends Command
{

    private readonly GenericIndexer $indexer;

    public function __construct(GenericIndexer $indexer, private readonly IndexableObjects $indexableObjects)
    {
        parent::__construct();
        $this->indexer = $indexer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Indexes specified entities or all entities if none specified')
            ->addArgument('entityClass', InputArgument::OPTIONAL, 'The entity class to sync')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityClass = $input->getArgument('entityClass');

        if ($entityClass) {
            if (!\is_string($entityClass) || !is_subclass_of($entityClass, IndexableEntityInterface::class)) {
                $output->writeln(\sprintf(
                    '<error>The class %s must implement IndexableEntityInterface.</error>',
                    \is_string($entityClass) ? $entityClass : \gettype($entityClass)
                ));

                return Command::FAILURE;
            }

            $this->updateSettings($entityClass);
        } else {
            $this->updateAllSettings($output);
        }

        return Command::SUCCESS;
    }

    private function updateSettings($entityClass) {

        $this->indexer->updateIndexSettings($entityClass);
    }


    private function updateAllSettings(OutputInterface $output)
    {
        $indexedClasses = $this->indexableObjects->getIndexedClasses();
        foreach ($indexedClasses as $entityClass) {
            $this->updateSettings($entityClass);
        }
    }
}
