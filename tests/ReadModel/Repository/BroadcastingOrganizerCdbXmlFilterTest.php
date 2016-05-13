<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

class BroadcastingOrganizerCdbXmlFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BroadcastingOrganizerCdbXmlFilter
     */
    private $broadcastingCdbXmlFilter;

    /**
     * @var string
     */
    private $cdbXmlFilesPath;

    public function setup()
    {
        $this->broadcastingCdbXmlFilter = new BroadcastingOrganizerCdbXmlFilter();
        $this->cdbXmlFilesPath = __DIR__;
    }

    /**
     * @test
     */
    public function it_always_matches_for_an_organizer()
    {
        $id = 'ORG-123';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor.xml')
        );

        $matches = $this->broadcastingCdbXmlFilter->matches($cdbXmlDocument);

        $this->assertTrue($matches);
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
