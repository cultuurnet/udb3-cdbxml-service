<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use ValueObjects\Web\Url;

class UitpasLabelProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClient;

    /**
     * @var UitpasLabelProvider
     */
    private $uitpasLabelProvider;

    protected function setUp()
    {
        $labelsUrl = Url::fromNative('http://udb-uitpas.dev/labels');

        $this->httpClient = $this->getMock(ClientInterface::class);

        $this->httpClient->method('get')
            ->willReturn(new Request('GET', (string) $labelsUrl));

        $this->uitpasLabelProvider = new UitpasLabelProvider(
            $this->httpClient,
            $labelsUrl
        );
    }

    /**
     * @test
     */
    public function it_can_get_all_uitpas_labels()
    {
        $labels = file_get_contents(__DIR__ . '/Samples/uitpas_labels.json');
        $this->httpClient->method('send')
            ->willReturn(new Response('200', null, $labels));

        $expectedUitpasLabels = LabelCollection::fromStrings(
            ["Paspartoe", "UiTPAS", "UiTPAS Gent"]
        );

        $uitpasLabels = $this->uitpasLabelProvider->getAll();

        $this->assertEquals($expectedUitpasLabels, $uitpasLabels);
    }

    /**
     * @test
     */
    public function it_stores_empty_array_when_send_request_fails()
    {
        $this->httpClient->method('send')
            ->willReturn(new Response('400'));

        $uitpasLabels = $this->uitpasLabelProvider->getAll();

        $this->assertEmpty($uitpasLabels);
    }
}
