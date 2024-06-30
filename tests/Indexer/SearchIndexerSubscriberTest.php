<?php

namespace Nramos\SearchIndexer\Tests\Indexer;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\IndexerInterface;
use Nramos\SearchIndexer\Indexer\SearchIndexerSubscriber;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use PHPUnit\Framework\TestCase;


class SearchIndexerSubscriberTest extends TestCase
{
    public function testPostPersist()
    {
        $house = new House();
        $house->setId(1);
        // Créer un mock pour IndexerInterface
        $indexerMock = $this->createMock(GenericIndexer::class);
        $indexerMock->expects($this->once())
            ->method('index')
            ->with([
                'entityClass' => 'Nramos\SearchIndexer\Tests\Entity\House',
                'id' => 1,
            ]);

        // Créer un mock pour LifecycleEventArgs
        $argsMock = $this->createMock(LifecycleEventArgs::class);
        $argsMock->expects($this->once())
            ->method('getObject')
            ->willReturn($house); // Mock your entity with ID 1


        $subscriber = new SearchIndexerSubscriber($indexerMock);
        $subscriber->postPersist($argsMock);
    }
    public function testHouseTypeNotIndexed()
    {
        // Mock IndexerInterface
        $indexerMock = $this->createMock(GenericIndexer::class);
        $indexerMock->expects($this->never())->method('index'); // On s'attend à ce que la méthode index ne soit jamais appelée

        // Créer un mock pour LifecycleEventArgs avec une entité HouseType non annotée
        $houseTypeMock = new HouseType();
        $houseTypeMock->setId(1);
        $argsMock = $this->createMock(LifecycleEventArgs::class);
        $argsMock->expects($this->once())->method('getObject')->willReturn($houseTypeMock);

        // Créer le subscriber avec le mock de l'indexer
        $subscriber = new SearchIndexerSubscriber($indexerMock);

        // Appeler postPersist sur le subscriber avec les arguments mockés
        $subscriber->postPersist($argsMock);
    }


}