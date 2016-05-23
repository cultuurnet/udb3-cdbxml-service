<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

class BroadcastingOfferCdbXmlFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BroadcastingOfferCdbXmlFilter
     */
    private $broadcastingCdbXmlFilter;

    /**
     * @var string
     */
    private $cdbXmlFilesPath;

    public function setup()
    {
        $this->broadcastingCdbXmlFilter = new BroadcastingOfferCdbXmlFilter();
        $this->cdbXmlFilesPath = __DIR__;
    }

    /**
     * @test
     */
    public function it_matches_a_place_and_returns_true()
    {
        $id = 'MY-PLACE-123';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place.xml')
        );

        $matches = $this->broadcastingCdbXmlFilter->matches($cdbXmlDocument);

        $this->assertTrue($matches);
    }

    /**
     * @test
     */
    public function it_does_not_match_a_place_and_returns_false()
    {
        $id = 'MY-PLACE-123';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event.xml')
        );

        $matches = $this->broadcastingCdbXmlFilter->matches($cdbXmlDocument);

        $this->assertFalse($matches);
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents($this->cdbXmlFilesPath . '/samples/' . $fileName);
    }
}
