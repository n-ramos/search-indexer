<?php
namespace Nramos\SearchIndexer\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;
use Nramos\SearchIndexer\Indexer\IndexableObjects;
use Nramos\SearchIndexer\Tests\Command\IndexEntitiesCommandTest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see IndexEntitiesCommandTest
 */
#[AsCommand(
    name: 'search:import',
    description: 'Index entities into search engine',
)]
class IndexEntitiesCommand extends Command
{
    private readonly EntityManagerInterface $entityManager;
    private readonly GenericIndexer $indexer;

    // Taille du lot par défaut
    private const DEFAULT_BATCH_SIZE = 100;

    public function __construct(
        EntityManagerInterface $entityManager,
        GenericIndexer $indexer,
        private readonly IndexableObjects $indexableObjects
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->indexer = $indexer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Indexes specified entities or all entities if none specified')
            ->addArgument('entityClass', InputArgument::OPTIONAL, 'The entity class to index')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Number of entities to process in a batch', self::DEFAULT_BATCH_SIZE)
            ->addOption('skip-clean', 's', InputOption::VALUE_NONE, 'Skip removing entities before indexing')
            ->addOption('bulk', null, InputOption::VALUE_NONE, 'Use bulk indexing when possible')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityClass = $input->getArgument('entityClass');
        $batchSize = (int) $input->getOption('batch-size');
        $skipClean = (bool) $input->getOption('skip-clean');
        $useBulk = (bool) $input->getOption('bulk');

        if ($entityClass) {
            if (!\is_string($entityClass) || !is_subclass_of($entityClass, IndexableEntityInterface::class)) {
                $output->writeln(\sprintf(
                    '<error>The class %s must implement IndexableEntityInterface.</error>',
                    \is_string($entityClass) ? $entityClass : \gettype($entityClass)
                ));
                return Command::FAILURE;
            }

            // @var class-string<IndexableEntityInterface> $entityClass
            if (!$skipClean) {
                $this->removeEntities($entityClass, $output);
            }

            $this->indexEntities($entityClass, $output, $batchSize, $useBulk);
        } else {
            $this->indexAllEntities($output, $batchSize, $skipClean, $useBulk);
        }

        return Command::SUCCESS;
    }

    /**
     * @param class-string<IndexableEntityInterface> $entityClass
     */
    private function indexEntities(
        string $entityClass,
        OutputInterface $output,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        bool $useBulk = false
    ): void {
        $repository = $this->entityManager->getRepository($entityClass);

        // Compter le nombre total d'entités pour la barre de progression
        $queryBuilder = $repository->createQueryBuilder('e');
        $totalCount = $queryBuilder->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        $output->writeln(\sprintf('Indexing %d entities of class %s...', $totalCount, $entityClass));
        $progressBar = new ProgressBar($output, $totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        // Traitement par lots
        $offset = 0;
        $entitiesBatch = [];

        while ($offset < $totalCount) {
            $queryBuilder = $repository->createQueryBuilder('e');
            $query = $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getQuery();

            // Utiliser Paginator pour éviter les problèmes avec les collections
            $paginator = new Paginator($query, false);

            if ($useBulk && method_exists($this->indexer, 'bulkIndex')) {
                // Collecter les entités pour l'indexation en bloc
                $entitiesToIndex = [];
                foreach ($paginator as $entity) {
                    if ($entity instanceof IndexableEntityInterface) {
                        $entitiesToIndex[] = $entity;
                    }
                }

                if (!empty($entitiesToIndex)) {
                    $this->indexer->bulkIndex($entitiesToIndex);
                    $progressBar->advance(count($entitiesToIndex));
                }
            } else {
                // Indexation individuelle
                foreach ($paginator as $entity) {
                    if ($entity instanceof IndexableEntityInterface) {
                        $this->indexer->index($entity);
                        $progressBar->advance();
                    }
                }
            }

            // Nettoyer l'EntityManager pour éviter les fuites de mémoire
            $this->entityManager->clear();

            $offset += $batchSize;
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(\sprintf('<info>Successfully indexed all entities of class %s.</info>', $entityClass));
    }

    private function removeEntities(string $entityClass, OutputInterface $output): void
    {
        $output->writeln(\sprintf('Removing all entities of class %s from index...', $entityClass));
        $this->indexer->clean($entityClass);
        $output->writeln(\sprintf('<info>Removed all entities of class %s from index.</info>', $entityClass));
    }

    private function indexAllEntities(
        OutputInterface $output,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        bool $skipClean = false,
        bool $useBulk = false
    ): void {
        $indexedClasses = $this->indexableObjects->getIndexedClasses();
        $output->writeln(\sprintf('Indexing %d entity classes...', count($indexedClasses)));

        foreach ($indexedClasses as $entityClass) {
            if (!$skipClean) {
                $this->removeEntities($entityClass, $output);
            }
            $this->indexEntities($entityClass, $output, $batchSize, $useBulk);
        }

        $output->writeln('<info>All entity classes have been indexed successfully.</info>');
    }
}