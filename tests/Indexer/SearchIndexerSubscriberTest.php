<?php

namespace Nramos\SearchIndexer\Tests\Indexer;

use Doctrine\ORM\Events;
use Nramos\SearchIndexer\Indexer\GenericIndexer;
use Nramos\SearchIndexer\Indexer\SearchIndexerSubscriber;
use Nramos\SearchIndexer\Tests\Entity\House;
use Nramos\SearchIndexer\Tests\Entity\HouseType;
use Nramos\SearchIndexer\Tests\Traits\EntityManagerInterfaceTrait;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small] final class SearchIndexerSubscriberTest extends TestCase
{
    use EntityManagerInterfaceTrait;

    public function testPostPersist()
    {
        $em = $this->createEntityManager();
        $evm = $em->getEventManager();

        $house = new House();
        $house->setName('Persisted House');
        $house->setPrice(1_500);
        $house->setId(1);
        $indexerMock = $this->createMock(GenericIndexer::class);

        $indexerMock->expects(self::once())
            ->method('index')
            ->with($house)
        ;

        $subscriber = new SearchIndexerSubscriber($indexerMock);
        $evm->addEventListener(Events::postPersist, $subscriber);

        $em->persist($house);
        $em->flush();
    }

    public function testPostUpdate()
    {
        $em = $this->createEntityManager();
        $evm = $em->getEventManager();

        $house = new House();
        $house->setName('Updated House');
        $house->setPrice(2_000);
        $em->persist($house);
        $em->flush();

        $house->setPrice(2_500);

        $indexerMock = $this->createMock(GenericIndexer::class);
        $indexerMock->expects(self::once())
            ->method('index')
            ->with($house)
        ;

        $subscriber = new SearchIndexerSubscriber($indexerMock);
        $evm->addEventListener(Events::postUpdate, $subscriber);

        $em->flush();
    }

    public function testHouseTypeNotIndexed()
    {
        $em = $this->createEntityManager();
        $evm = $em->getEventManager();

        $indexerMock = $this->createMock(GenericIndexer::class);
        $indexerMock->expects(self::never())->method('index');

        $subscriber = new SearchIndexerSubscriber($indexerMock);
        $evm->addEventListener(Events::postPersist, $subscriber);

        $houseType = new HouseType();  // Entity not marked for indexing
        $houseType->setTypeName('Non-indexed Type');
        $em->persist($houseType);
        $em->flush();
    }

    public function testGetSubscribedEvents()
    {
        $subscriber = new SearchIndexerSubscriber($this->createMock(GenericIndexer::class));

        $events = $subscriber->getSubscribedEvents();

        self::assertContains(Events::postPersist, $events);
        self::assertContains(Events::postUpdate, $events);
        self::assertCount(2, $events);
    }
}
