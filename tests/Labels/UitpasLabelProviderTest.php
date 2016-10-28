<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use ValueObjects\Web\Url;

class UitpasLabelProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UitpasLabelProvider
     */
    private $uitpasLabelProvider;

    protected function setUp()
    {
        $labelsUrl = Url::fromNative('http://udb-uitpas.dev/labels');

        $httpClient = $this->getMock(ClientInterface::class);

        $httpClient->method('get')
            ->willReturn(new Request('GET', (string)$labelsUrl));

        $labels = file_get_contents(__DIR__ . '/uitpas_labels.json');
        $httpClient->method('send')
            ->willReturn(new Response('200', null, $labels));

        $this->uitpasLabelProvider = new UitpasLabelProvider(
            $httpClient,
            $labelsUrl
        );
    }

    /**
     * @test
     */
    public function it_can_get_all_uitpas_labels()
    {
        $expectedUitpasLabels = [
            "PASPARTOE" => "Paspartoe",
            "UITPAS" => "UiTPAS",
            "UITPAS_GENT" => "UiTPAS Gent"
        ];

        $uitpasLabels = $this->uitpasLabelProvider->getAll();

        $this->assertEquals($expectedUitpasLabels, $uitpasLabels);
    }
}
