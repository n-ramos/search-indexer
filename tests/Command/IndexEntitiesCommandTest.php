<?php

namespace Nramos\SearchIndexer\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Nramos\SearchIndexer\Command\IndexEntitiesCommand;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;
use Nramos\SearchIndexer\Tests\Entity\House;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Small] final class IndexEntitiesCommandTest extends TestCase
{
    private $entityManager;
    private $indexer;
    private $commandTester;
    private $indexedClasses;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->indexer = $this->createMock(GenericIndexer::class);
        $this->indexedClasses = [House::class];

        $application = new Application();
        $command = new IndexEntitiesCommand($this->entityManager, $this->indexer, $this->indexedClasses);
        $application->add($command);

        $this->commandTester = new CommandTester($application->find('search:import'));
    }

    public function testCommandFailsIfClassDoesNotImplementInterface()
    {
        $this->commandTester->execute(['entityClass' => stdClass::class]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The class stdClass must implement IndexableEntityInterface.', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testCommandIndexesSpecifiedEntityClass()
    {
        $entity = $this->createMock(IndexableEntityInterface::class);
        $entityClass = $entity::class;
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$entity]);

        $this->entityManager->method('getRepository')->with($entityClass)->willReturn($repository);

        $this->indexer->expects(self::once())
            ->method('index')
            ->with(['entityClass' => $entityClass, 'id' => $entity->getId()])
        ;

        $this->commandTester->execute(['entityClass' => $entityClass]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Indexed all entities of class', $output);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCommandIndexesAllEntitiesWhenNoClassSpecified()
    {
        $metaData = [
            $this->createMock(ClassMetadata::class),
        ];
        $reflectionClass = new ReflectionClass(House::class);
        $metaData[0]->method('getReflectionClass')->willReturn($reflectionClass);
        $metaData[0]->method('getName')->willReturn(House::class);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn($metaData);

        $this->entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $repository = $this->createMock(EntityRepository::class);
        $entity = $this->createMock(House::class);
        $repository->method('findAll')->willReturn([$entity]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->indexer->expects(self::once())->method('clean')->with(House::class);
        $this->indexer->expects(self::once())->method('index')->with(['entityClass' => House::class, 'id' => $entity->getId()]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Removed all entities of class', $output);
        self::assertStringContainsString('Indexed all entities of class', $output);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCommandFailsIfEntityClassDoesNotExistOrImplementInterface()
    {
        $nonExistentClass = 'NonExistentClass';

        $this->commandTester->execute(['entityClass' => $nonExistentClass]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The class NonExistentClass must implement IndexableEntityInterface.', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        // Test for a class that exists but does not implement the interface
        $invalidEntityClass = stdClass::class;

        $this->commandTester->execute(['entityClass' => $invalidEntityClass]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The class stdClass must implement IndexableEntityInterface.', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }
}
