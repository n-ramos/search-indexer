<?php

namespace Nramos\SearchIndexer\Tests;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nramos\SearchIndexer\Annotation\MapProperty;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\SearchClientInterface;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

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

    public function testExtractData()
    {
        // Création d'une instance de House pour tester l'extraction de données
        $houseType = new HouseType();
        $houseType->setTypeName('Example Type');

        $house = new House();
        $house->setName('Example House');
        $house->setPrice(1000);
        $house->setHouseType($houseType);

        // Appel de la méthode à tester
        $data = $this->indexer->extractData($house);

        // Assertions sur les données extraites
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals('Example House', $data['name']);

        $this->assertArrayHasKey('price', $data);
        $this->assertEquals(1000, $data['price']);

        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('Example Type', $data['type']);
    }

    public function testCleanIndex()
    {
        // Configuration du client de recherche pour vérifier les appels
        $this->searchClient->expects($this->once())
            ->method('clear')
            ->with($this->equalTo('houses'));

        // Appel de la méthode à tester
        $this->indexer->clean(House::class);

    }



}
