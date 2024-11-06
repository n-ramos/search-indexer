<?php

namespace Nramos\SearchIndexer\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;
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
    name: 'search:import',
    description: 'Add a short description for your command',
)]
class IndexEntitiesCommand extends Command
{
    private readonly EntityManagerInterface $entityManager;

    private readonly GenericIndexer $indexer;

    public function __construct(EntityManagerInterface $entityManager, GenericIndexer $indexer, private readonly array $indexedClasses = [])
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->indexer = $indexer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Indexes specified entities or all entities if none specified')
            ->addArgument('entityClass', InputArgument::OPTIONAL, 'The entity class to index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityClass = $input->getArgument('entityClass');

        if ($entityClass) {
            if (!\is_string($entityClass) || !is_subclass_of($entityClass, IndexableEntityInterface::class)) {
                $output->writeln(sprintf(
                    '<error>The class %s must implement IndexableEntityInterface.</error>',
                    \is_string($entityClass) ? $entityClass : \gettype($entityClass)
                ));

                return Command::FAILURE;
            }

            // @var class-string<IndexableEntityInterface> $entityClass
            $this->indexEntities($entityClass, $output);
        } else {
            $this->indexAllEntities($output);
        }

        return Command::SUCCESS;
    }

    /**
     * @param class-string<IndexableEntityInterface> $entityClass
     */
    private function indexEntities(string $entityClass, OutputInterface $output): void
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();

        foreach ($entities as $entity) {
            if ($entity instanceof IndexableEntityInterface) {
                $this->indexer->index(['entityClass' => $entityClass, 'id' => $entity->getId()]);
            }
        }

        $output->writeln(sprintf('Indexed all entities of class %s.', $entityClass));
    }

    private function removeEntities(string $entityClass, OutputInterface $output): void
    {
        $this->indexer->clean($entityClass);

        $output->writeln(sprintf('Removed all entities of class %s from index.', $entityClass));
    }

    private function indexAllEntities(OutputInterface $output): void
    {
        foreach($this->indexedClasses as $entityClass) {
            $this->removeEntities($entityClass, $output);
            $this->indexEntities($entityClass, $output);
        }
    }
}
