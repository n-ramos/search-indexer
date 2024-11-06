<?php

namespace Nramos\SearchIndexer\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Post;
use Nramos\SearchIndexer\Command\IndexEntitiesCommand;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use Nramos\SearchIndexer\Tests\Traits\EntityManagerInterfaceTrait;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Small] final class IndexEntitiesCommandTest extends TestCase
{
    use EntityManagerInterfaceTrait;
    private CommandTester $commandTester;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createEntityManager();

        $indexer = $this->createMock(GenericIndexer::class);
        $this->indexedClasses = [House::class, Post::class];

        $application = new Application();
        $command = new IndexEntitiesCommand($this->entityManager, $indexer, $this->indexedClasses);
        $application->add($command);

        $this->commandTester = new CommandTester($application->find('search:import'));
    }

    public function testCommandFailsIfClassDoesNotImplementInterface()
    {
        $this->commandTester->execute(['entityClass' => HouseType::class]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The class '.HouseType::class.' must implement IndexableEntityInterface.', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testCommandIndexesSpecifiedEntityClass()
    {
        $entity = new House();
        $entity->setName('House');
        $entity->setPrice(1_000);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->setUpEntityManagerMock($entity::class);
        $listOfEntities = $this->entityManager->getRepository($entity::class)->findAll();
        self::assertContains($entity, $listOfEntities, 'Entity not found in repository');
        $this->commandTester->execute(['entityClass' => $entity::class]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Indexed all entities of class', $output);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCommandIndexesAllEntitiesWhenNoClassSpecified()
    {
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Removed all entities of class ', $output);
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

        $invalidEntityClass = stdClass::class;
        $this->commandTester->execute(['entityClass' => $invalidEntityClass]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('The class stdClass must implement IndexableEntityInterface.', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    private function setUpEntityManagerMock(string $entityClass): void
    {
        $indexer = $this->createMock(GenericIndexer::class);

        $application = new Application();
        $command = new IndexEntitiesCommand($this->entityManager, $indexer, [$entityClass]);
        $application->add($command);

        $this->commandTester = new CommandTester($application->find('search:import'));
    }
}
