<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Title;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

class EventBusCdbXmlPublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IriGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $iriGenerator;

    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventBus;

    /**
     * @var CdbXmlPublisherInterface
     */
    protected $publisher;

    public function setUp()
    {
        $this->eventBus = $this->getMock(EventBusInterface::class);
        $this->iriGenerator = $this->getMock(IriGeneratorInterface::class);

        $this->publisher = new EventBusCdbXmlPublisher(
            $this->iriGenerator,
            $this->eventBus
        );
    }

    /**
     * @test
     */
    public function it_should_add_the_author_and_public_url_when_publishing_cbdxml()
    {
        $authorId = 'DA215A10-06E5-493B-B069-71AC6EBE1E5D';
        $documentId = 'A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationUrl = 'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';
        $publicationDate = '2016-04-12T10:58:55+00:00';
        $originalDomainEvent = new EventCreated(
            $documentId,
            new Title('Some Event'),
            new EventType('some', 'type'),
            new Location('q', 'w', 'e', 'r', 't', 'y'),
            $this->getMock(CalendarInterface::class)
        );
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['user_id' => $authorId]),
            $originalDomainEvent,
            DateTime::fromString($publicationDate)
        );
        $document = new CdbXmlDocument(
            $documentId,
            '<?xml version=\'1.0\'?><_/>'
        );

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn($publicationUrl);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) use ($publicationUrl, $authorId) {
                        /** @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return $publicationUrl === (string) $domainMessage->getPayload()->getUrl() &&
                               $authorId === (string) $domainMessage->getPayload()->getAuthor();
                    }
                )
            );

        $this->publisher->publish($document, $originalDomainMessage);
    }

    /**
     * @test
     */
    public function it_should_broadcast_an_update_event_when_an_item_was_already_published()
    {
        $publicationUrl = 'http://foo.be/item/A59682E1-6745-4AF3-8B7F-FB8A8FE895D5';

        $this->iriGenerator
            ->expects($this->once())
            ->method('iri')
            ->willReturn($publicationUrl);

        $originalDomainEvent = new TitleTranslated($publicationUrl, new Language('nl'), new StringLiteral('c'));
        $originalDomainMessage = new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata(['user_id' => 'me-me']),
            $originalDomainEvent,
            DateTime::now()
        );
        $document = new CdbXmlDocument(
            'A59682E1-6745-4AF3-8B7F-FB8A8FE895D5',
            '<?xml version=\'1.0\'?><_/>'
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function ($eventStream) use ($publicationUrl) {
                        /** @var DomainMessage $domainMessage */
                        $domainMessage = $eventStream->getIterator()->current();

                        return is_a($domainMessage->getPayload(), EventUpdated::class);
                    }
                )
            );

        $this->publisher->publish($document, $originalDomainMessage);
    }
}
