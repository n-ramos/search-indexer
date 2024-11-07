<?php

namespace Nramos\SearchIndexer\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\SearchClientInterface;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use Nramos\SearchIndexer\Tests\Traits\EntityManagerInterfaceTrait;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small] final class GenericIndexerTest extends TestCase
{
    use EntityManagerInterfaceTrait;
    private SearchClientInterface $searchClient;
    private EntityManagerInterface $entityManager;
    private GenericIndexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialisation des mocks pour EntityManager et SearchClient
        $this->entityManager = $this->createEntityManager();
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
        $this->searchClient->expects(self::once())
            ->method('delete')
            ->with(self::equalTo('houses'))
        ;

        // Appel de la méthode à tester
        $this->indexer->remove($house);

        // Assertions supplémentaires si nécessaire
        // Ex: Vérification que la suppression a été effectuée correctement
    }

    public function testEdgeCases()
    {
        // Cas limite : HouseType n'est pas indexé
        $houseType = new HouseType();
        $houseType->setTypeName('Example Type');
        $this->entityManager->persist($houseType);
        $this->entityManager->flush();

        $this->searchClient->expects(self::never())
            ->method('put')
        ;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Entity class '.HouseType::class.' is not mapped to an index.');

        $this->indexer->index($houseType);
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
        self::assertArrayHasKey('name', $data);
        self::assertSame('Example House', $data['name']);

        self::assertArrayHasKey('price', $data);
        self::assertSame(1000, $data['price']);

        self::assertArrayHasKey('type', $data);
        self::assertSame('Example Type', $data['type']);
    }

    public function testCleanIndex()
    {
        // Configuration du client de recherche pour vérifier les appels
        $this->searchClient->expects(self::once())
            ->method('clear')
            ->with(self::equalTo('houses'))
        ;

        // Appel de la méthode à tester
        $this->indexer->clean(House::class);
    }
}
