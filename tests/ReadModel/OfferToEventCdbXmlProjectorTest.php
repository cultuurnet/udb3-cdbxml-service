<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\PlaceService;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;

class OfferToEventCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var OfferToEventCdbXmlProjector
     */
    private $projector;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var PlaceService | \PHPUnit_Framework_MockObject_MockObject
     */
    private $placeService;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');

        $this->placeService = $this->getMock(PlaceService::class, array(), array(), 'placeServiceMock', false);

        $this->projector = (
        new OfferToEventCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->placeService,
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            )
        )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);

        $this->metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
            ]
        );
    }

    /**
     * @test
     */
    public function it_projects_event_created()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $timestamps = [
            new Timestamp(
                '2014-01-31T12:00:00+01:00',
                '2014-01-31T15:00:00+01:00'
            ),
            new Timestamp(
                '2014-02-20T12:00:00+01:00',
                '2014-02-20T15:00:00+01:00'
            ),
        ];

        $event = new EventCreated(
            $id,
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            new Location('LOCATION-ABC-123', '$name', '$country', '$locality', '$postalcode', '$street'),
            new Calendar('multiple', '2014-01-31T12:00:00+01:00', '2014-02-20T15:00:00+01:00', $timestamps),
            new Theme('1.7.6.0.0', 'Griezelfilm of horror')
        );
        
        $this->placeService->expects($this->once())
            ->method('getEntity')
            ->with('LOCATION-ABC-123')

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }
}
