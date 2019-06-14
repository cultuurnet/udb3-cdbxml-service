<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ValueObjects\Identity\UUID;

class AbstractCdbXmlProjectorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayCache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cdbXmlFilesPath;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var AbstractCdbXmlProjector|PHPUnit_Framework_MockObject_MockObject
     */
    protected $projector;

    /**
     * @var CdbXmlDocument[]
     */
    protected $publishedCdbXmlDocuments;

    /**
     * @var CacheDocumentRepository
     */
    protected $repository;

    /**
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    protected function assertCdbXmlDocumentInRepository(CdbXmlDocument $expectedCdbXmlDocument)
    {
        $cdbId = $expectedCdbXmlDocument->getId();
        $actualCdbXmlDocument = $this->repository->get($cdbId);

        if (is_null($actualCdbXmlDocument)) {
            $this->fail("CdbXmlDocument for CdbId {$cdbId} not found.");
        }

        $this->assertEquals($expectedCdbXmlDocument->getCdbXml(), $actualCdbXmlDocument->getCdbXml());
    }

    /**
     * @param string $entityId
     * @param object $event
     * @param Metadata|null $metadata
     * @param DateTime $dateTime
     * @return DomainMessage
     */
    protected function createDomainMessage(
        $entityId,
        $event,
        Metadata $metadata = null,
        DateTime $dateTime = null
    ) {
        if (is_null($metadata)) {
            $metadata = new Metadata();
        }

        if (is_null($dateTime)) {
            $dateTime = DateTime::now();
        }

        return new DomainMessage(
            $entityId,
            1,
            $metadata,
            $event,
            $dateTime
        );
    }

    /**
     * @return array
     */
    public function entityDataProvider()
    {
        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T15:00:00+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T15:00:00+01:00')
            ),
        ];

        $location = new LocationId(UUID::generateAsString());

        return [
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Language('nl'),
                    new Title('Griezelfilm of horror'),
                    new EventType('0.50.6.0.0', 'film'),
                    $location,
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                        $timestamps
                    ),
                    new Theme('1.7.6.0.0', 'Griezelfilm of horror')
                ),
                'event.xml',
            ],
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24481',
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24481',
                    new Language('nl'),
                    new Title('Griezelfilm of horror'),
                    new EventType('0.50.6.0.0', 'film'),
                    $location,
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                        $timestamps
                    ),
                    new Theme('1.7.6.0.0', 'Griezelfilm of horror'),
                    \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-04-23T15:30:06')
                ),
                'event-with-publication-date.xml',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider entityDataProvider
     * @param $id
     * @param SerializableInterface $event
     * @param $cdbXmlFileName
     */
    public function it_projects_entities(
        $id,
        SerializableInterface $event,
        $cdbXmlFileName
    ) {
        $this->projector
            ->expects($this->any())
            ->method('getHandlers')
            ->willReturn(
                array(EventCreated::class => 'applyFoo')
            );

        $this->projector
            ->expects($this->any())
            ->method('applyFoo')
            ->willReturnCallback(
                function (SerializableInterface $payload) {
                    switch (get_class($payload)) {
                        case EventCreated::class:
                            /* @var EventCreated $payload */
                            $cdbXmlDocument = $this->projector->getCdbXmlDocument($payload->getEventId());
                            return array($cdbXmlDocument);

                        default:
                            return '';

                    }
                }
            );

        $domainMessage = $this->createDomainMessage($id, $event);
        $this->projector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($cdbXmlFileName)
        );

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     * @dataProvider entityDataProvider
     * @param $id
     * @param SerializableInterface $event
     */
    public function it_logs_an_error_when_handler_throws_an_exception(
        $id,
        SerializableInterface $event
    ) {
        $this->projector
            ->expects($this->any())
            ->method('getHandlers')
            ->willReturn(
                array(EventCreated::class => 'applyFoo')
            );

        $this->projector
            ->expects($this->any())
            ->method('applyFoo')
            ->willThrowException(new Exception());

        $domainMessage = $this->createDomainMessage($id, $event);
        $this->logger->expects($this->at(1))->method('error');
        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     * @dataProvider entityDataProvider
     * @param $id
     * @param SerializableInterface $event
     */
    public function it_logs_info_when_no_handler_is_found(
        $id,
        SerializableInterface $event
    ) {
        $domainMessage = $this->createDomainMessage($id, $event);
        $this->logger->expects($this->at(1))->method('info');
        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_no_cdbxml_document_found()
    {
        $this->setExpectedException(RuntimeException::class);
        $this->projector->getCdbXmlDocument('foo');
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents($this->cdbXmlFilesPath . '/' . $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->cdbXmlFilesPath = __DIR__ . '/Repository/samples/';
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);

        $this->projector = $this->getMockForAbstractClass(
            AbstractCdbXmlProjector::class,
            array(
                $this->repository,
            ),
            '',
            true,
            true,
            true,
            array('applyFoo')
        );

        foreach ($this->entityDataProvider() as $entity) {
            $this->repository->save(
                new CdbXmlDocument(
                    $entity[0],
                    $this->loadCdbXmlFromFile($entity[2])
                )
            );
        }

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);
    }
}
