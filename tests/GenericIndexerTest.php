<?php

namespace Nramos\SearchIndexer\Tests;


use Doctrine\ORM\EntityManagerInterface;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\SearchClientInterface;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use PHPUnit\Framework\TestCase;

class GenericIndexerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private SearchClientInterface $searchClient;
    private GenericIndexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialisation des mocks pour EntityManager et SearchClient
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->searchClient = $this->createMock(SearchClientInterface::class);

        // Initialisation de l'indexer avec les mocks
        $this->indexer = new GenericIndexer($this->entityManager, $this->searchClient);
    }

    public function testIndexHouse()
    {

        // Préparation des données de test
        $houseType = new HouseType();
        $houseType->setTypeName('Example Type');

        $house = new House();
        $house->setName('Example House');
        $house->setPrice(1000);
        $house->setHouseType($houseType);
        // ... Set other properties as needed
        $entityRepositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $entityRepositoryMock->method('find')
            ->willReturn($house);

        // Configuration de l'EntityManager pour retourner l'entité House lors de la recherche
        $this->entityManager->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($entityRepositoryMock) {
                if ($entityClass === House::class) {
                    return $entityRepositoryMock;
                }
                return null; // Gérer d'autres cas si nécessaire
            });

        // Configuration du client de recherche pour vérifier les appels
        $this->searchClient->expects($this->once())
            ->method('put')
            ->with($this->equalTo('houses'), $this->isType('array'));

        // Appel de la méthode à tester
        $data = [
            'entityClass' => House::class,
            'id' => 1,
        ];
        $this->indexer->index($data);

        // Assertions supplémentaires si nécessaire
        // Ex: Vérification que les paramètres d'indexation sont corrects
    }

    public function testRemoveHouse()
    {
        // Création d'une instance de House pour simuler la suppression
        $houseType = new HouseType();
        $houseType->setTypeName('Example Type');

        $house = new House();
        $house->setName('Example House');
        $house->setPrice(1000);
        $house->setHouseType($houseType);

        // Configuration du client de recherche pour vérifier les appels
        $this->searchClient->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('houses'), $this->equalTo(1));

        // Appel de la méthode à tester
        $this->indexer->remove(1, House::class);

        // Assertions supplémentaires si nécessaire
        // Ex: Vérification que la suppression a été effectuée correctement
    }

    public function testEdgeCases()
    {
        // Cas limite : HouseType n'est pas indexé
        $houseType = new HouseType();
        $houseType->setTypeName('Example Type');

        // Configuration du mock EntityManager pour retourner une EntityRepository valide
        $entityRepositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $entityRepositoryMock->method('find')
            ->willReturn($houseType); // Simule que HouseType est récupéré

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($entityRepositoryMock) {
                if ($entityClass === HouseType::class) {
                    return $entityRepositoryMock;
                }
                return null; // Gérer d'autres cas si nécessaire
            });

        // Configuration du client de recherche - on ne s'attend pas à ce que put() soit appelé
        $this->searchClient->expects($this->never())
            ->method('put');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Entity class " . HouseType::class . " is not mapped to an index.");

        // Appel de la méthode à tester
        $data = [
            'entityClass' => HouseType::class,
            'id' => 1,
        ];
        $this->indexer->index($data);

        // Assertions supplémentaires si nécessaire
        // Ex: Vérification qu'aucun appel à put() n'a été fait pour HouseType
    }
}
