<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\OfferLabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Offer\OfferType;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

class LabelToItemCdbxmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var LabelToItemCdbxmlProjector
     */
    protected $projector;

    /**
     * @var ReadRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $relationRepository;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');
        $this->relationRepository = $this->getMock(ReadRepositoryInterface::class);

        date_default_timezone_set('Europe/Brussels');

        $this->projector = (
        new LabelToItemCdbxmlProjector(
            $this->repository,
            $this->relationRepository,
            new CdbXmlDocumentFactory('3.3')
        ));
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_places_which_have_a_label_made_visible()
    {
        $labelId = new UUID();
        $placeId = new StringLiteral('C4ACF936-1D5F-48E8-B2EC-863B313CBDE6');

        $this->relationRepository
            ->method('getOfferLabelRelations')
            ->willReturn(
                [
                    new OfferLabelRelation(
                        $labelId,
                        OfferType::PLACE(),
                        $placeId
                    ),
                ]
            );

        $madeVisible = new MadeVisible($labelId, new LabelName('foobar'));

        $domainMessage = $this->createDomainMessage(
            (string) $labelId,
            $madeVisible,
            new Metadata()
        );

        $placeCdbXmlDocument = new CdbXmlDocument(
            (string) $placeId,
            $this->loadCdbXmlFromFile('actor-place-with-keyword-visible-false.xml')
        );
        $this->repository->save($placeCdbXmlDocument);

        $expectedDocument = new CdbXmlDocument(
            (string) $placeId,
            $this->loadCdbXmlFromFile('actor-place-with-keyword.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_places_which_have_a_label_made_invisible()
    {
        $labelId = new UUID();
        $placeId = new StringLiteral('C4ACF936-1D5F-48E8-B2EC-863B313CBDE6');

        $this->relationRepository
            ->method('getOfferLabelRelations')
            ->willReturn(
                [
                    new OfferLabelRelation(
                        $labelId,
                        OfferType::PLACE(),
                        $placeId
                    ),
                ]
            );

        $madeInvisible = new MadeInvisible($labelId, new LabelName('foobar'));

        $domainMessage = $this->createDomainMessage(
            (string) $labelId,
            $madeInvisible,
            new Metadata()
        );

        $placeCdbXmlDocument = new CdbXmlDocument(
            (string) $placeId,
            $this->loadCdbXmlFromFile('actor-place-with-keyword.xml')
        );
        $this->repository->save($placeCdbXmlDocument);

        $expectedDocument = new CdbXmlDocument(
            (string) $placeId,
            $this->loadCdbXmlFromFile('actor-place-with-keyword-visible-false.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_events_which_have_a_label_made_visible()
    {
        $labelId = new UUID();
        $eventId = new StringLiteral('404EE8DE-E828-9C07-FE7D12DC4EB24480');

        $this->relationRepository
            ->method('getOfferLabelRelations')
            ->willReturn(
                [
                    new OfferLabelRelation(
                        $labelId,
                        OfferType::EVENT(),
                        $eventId
                    ),
                ]
            );

        $madeVisible = new MadeVisible($labelId, new LabelName('foobar'));

        $domainMessage = $this->createDomainMessage(
            (string) $labelId,
            $madeVisible,
            new Metadata()
        );

        $eventCdbXmlDocument = new CdbXmlDocument(
            (string) $eventId,
            $this->loadCdbXmlFromFile('event-with-keyword-visible-false.xml')
        );
        $this->repository->save($eventCdbXmlDocument);

        $expectedDocument = new CdbXmlDocument(
            (string) $eventId,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_events_which_have_a_label_made_invisible()
    {
        $labelId = new UUID();
        $eventId = new StringLiteral('404EE8DE-E828-9C07-FE7D12DC4EB24480');

        $this->relationRepository
            ->method('getOfferLabelRelations')
            ->willReturn(
                [
                    new OfferLabelRelation(
                        $labelId,
                        OfferType::EVENT(),
                        $eventId
                    ),
                ]
            );

        $madeInvisible = new MadeInvisible($labelId, new LabelName('foobar'));

        $domainMessage = $this->createDomainMessage(
            (string) $labelId,
            $madeInvisible,
            new Metadata()
        );

        $eventCdbXmlDocument = new CdbXmlDocument(
            (string) $eventId,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );
        $this->repository->save($eventCdbXmlDocument);

        $expectedDocument = new CdbXmlDocument(
            (string) $eventId,
            $this->loadCdbXmlFromFile('event-with-keyword-visible-false.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }
}
