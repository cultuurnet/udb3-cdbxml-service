<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

class UitpasLabelFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LabelProviderInterface
     */
    private $uitpasLabelProvider;

    /**
     * @var UitpasLabelFilter
     */
    private $uitpasLabelFilter;

    protected function setUp()
    {
        $this->uitpasLabelProvider = $this->getMock(
            LabelProviderInterface::class
        );

        $this->uitpasLabelProvider->method('getAll')
            ->willReturn(LabelCollection::fromStrings(['Paspartoe', 'UiTPAS Dender']));

        $this->uitpasLabelFilter = new UitpasLabelFilter(
            $this->uitpasLabelProvider
        );
    }

    /**
     * @test
     */
    public function it_returns_only_uitpas_labels()
    {
        $labels = LabelCollection::fromStrings(
            ['2dotstwice', 'UiTPAS Dender', 'Cultuurnet', 'Paspartoe']
        );
        $expectedLabels = LabelCollection::fromStrings(
            ['Paspartoe', 'UiTPAS Dender']
        );

        $filterLabels = $this->uitpasLabelFilter->filter($labels);

        $this->assertEquals($expectedLabels, $filterLabels);
    }
}
