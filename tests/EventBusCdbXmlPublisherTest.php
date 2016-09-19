<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB2DomainEvents\ActorUpdated;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\Events\EventProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

class EventBusCdbXmlPublisherTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventBus;

    /**
     * @var IriOfferIdentifierFactory
     */
    protected $iriOfferIdentifierFactory;

    /**
     * @var EventBusCdbXmlPublisher
     */
    protected $publisher;

    /**
     * @var string
     */
    private $regex;

    public function setUp()
    {
        $this->eventBus = $this->getMock(EventBusInterface::class);

        $this->regex = 'https?://foo\.bar/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)';
        $this->iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            $this->regex
        );

        $this->publisher = new EventBusCdbXmlPublisher(
            $this->eventBus,
            $this->iriOfferIdentifierFactory
        );
    }

    /**
     * @test
     */
    public function it_should_add_the_author_and_public_url_when_publishing()
    {
        $authorId = 'DA215A10-06E5-493B-B069-71AC6EBE1E5D';
        $iri = 'https://foo.bar/event/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationUrl = 'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationDate = '2016-04-12T10:58:55+00:00';

        $originalDomainEvent = new EventProjectedToCdbXml(
            $iri,
            false
        );
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['user_id' => $authorId, 'id' => $publicationUrl]),
            $originalDomainEvent,
            DateTime::fromString($publicationDate)
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) use ($publicationUrl, $authorId) {
                        /* @var DomainEventStream $eventStream */
                        /* @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return $publicationUrl === (string) $domainMessage->getPayload()->getUrl() &&
                               $authorId === (string) $domainMessage->getPayload()->getAuthor();
                    }
                )
            );

        $this->publisher->handle($originalDomainMessage);
    }

    /**
     * @test
     */
    public function it_should_not_add_the_author_if_there_is_none()
    {
        $iri = 'https://foo.bar/event/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationUrl = 'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationDate = '2016-04-12T10:58:55+00:00';

        $originalDomainEvent = new EventProjectedToCdbXml(
            $iri,
            false
        );
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['id' => $publicationUrl]),
            $originalDomainEvent,
            DateTime::fromString($publicationDate)
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) {
                        /* @var DomainEventStream $eventStream */
                        /* @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return '' === (string) $domainMessage->getPayload()->getAuthor();
                    }
                )
            );

        $this->publisher->handle($originalDomainMessage);
    }

    /**
     * @test
     * @dataProvider domainEventDataProvider
     * @param string $publicationUrl
     * @param string $originalDomainEvent
     * @param string $expectedPayloadType
     */
    public function it_should_broadcast_an_event_depending_on_the_original_domain_event(
        $publicationUrl,
        $originalDomainEvent,
        $expectedPayloadType
    ) {
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['user_id' => 'me-me', 'id' => $publicationUrl]),
            $originalDomainEvent,
            DateTime::now()
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) use ($publicationUrl, $expectedPayloadType) {
                        /* @var DomainEventStream $eventStream */
                        /* @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return is_a($domainMessage->getPayload(), $expectedPayloadType);
                    }
                )
            );

        $this->publisher->handle($originalDomainMessage);
    }

    /**
     * @return array
     */
    public function domainEventDataProvider()
    {
        return [
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new OrganizerProjectedToCdbXml(
                    'A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    true
                ),
                ActorCreated::class,
            ],
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new OrganizerProjectedToCdbXml(
                    'A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    false
                ),
                ActorUpdated::class,
            ],
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new PlaceProjectedToCdbXml(
                    'https://foo.bar/place/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    true
                ),
                ActorCreated::class,
            ],
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new PlaceProjectedToCdbXml(
                    'https://foo.bar/place/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    false
                ),
                ActorUpdated::class,
            ],
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new EventProjectedToCdbXml(
                    'https://foo.bar/event/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    true
                ),
                EventCreated::class,
            ],
            [
                'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                new EventProjectedToCdbXml(
                    'https://foo.bar/event/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
                    false
                ),
                EventUpdated::class,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_publish_the_item_at_the_url_provided_by_the_metadata_id_property()
    {
        $iri = 'https://foo.bar/event/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationUrl = 'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';

        $originalDomainEvent = new EventProjectedToCdbXml(
            $iri,
            false
        );
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['user_id' => 'me-me', 'id' => $publicationUrl]),
            $originalDomainEvent,
            DateTime::now()
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) use ($publicationUrl) {
                        /* @var DomainEventStream $eventStream */
                        /* @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return $publicationUrl === (string) $domainMessage->getPayload()->getUrl();
                    }
                )
            );

        $this->publisher->handle($originalDomainMessage);
    }
}
