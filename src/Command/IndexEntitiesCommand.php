<?php

namespace Nramos\SearchIndexer\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nramos\SearchIndexer\Annotation\Map;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'search:import',
    description: 'Add a short description for your command',
)]
class IndexEntitiesCommand extends Command
{

    private $entityManager;
    private $indexer;

    public function __construct(EntityManagerInterface $entityManager, GenericIndexer $indexer)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->indexer = $indexer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Indexes specified entities or all entities if none specified')
            ->addArgument('entityClass', InputArgument::OPTIONAL, 'The entity class to index');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityClass = $input->getArgument('entityClass');

        if ($entityClass) {
            $this->indexEntities($entityClass, $output);
        } else {
            $this->indexAllEntities($output);
        }

        return Command::SUCCESS;
    }

    private function indexEntities(string $entityClass, OutputInterface $output): void
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();

        foreach ($entities as $entity) {

            $this->indexer->index(['entityClass' => $entityClass, 'id' => $entity->getId()]);
        }

        $output->writeln(sprintf('Indexed all entities of class %s.', $entityClass));
    }
    private function removeEntities(string $entityClass, OutputInterface $output): void
    {

        $this->indexer->clean($entityClass);

        $output->writeln(sprintf('Indexed all entities of class %s.', $entityClass));
    }

    private function indexAllEntities(OutputInterface $output): void
    {
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metaData as $meta) {
            $reflectionClass = $meta->getReflectionClass();
            $attributes = $reflectionClass->getAttributes(Map::class);

            if ($attributes) {
                $this->removeEntities($meta->getName(), $output);
                $this->indexEntities($meta->getName(), $output);
            }
        }
    }
}