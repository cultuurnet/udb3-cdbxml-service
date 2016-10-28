<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use Guzzle\Http\ClientInterface;
use ValueObjects\Web\Url;

class UitpasLabelProvider implements LabelProviderInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Url
     */
    private $labelsUrl;

    /**
     * @var string[]
     */
    private $uitpasLabels;

    /**
     * UitpasLabelProvider constructor.
     * @param ClientInterface $httpClient
     * @param Url $labelsUrl
     */
    public function __construct(
        ClientInterface $httpClient,
        Url $labelsUrl
    ) {
        $this->httpClient = $httpClient;
        $this->labelsUrl = $labelsUrl;
        $this->uitpasLabels = null;
    }

    /**
     * @return string[]
     */
    public function getAll()
    {
        if (is_null($this->uitpasLabels)) {
            $this->initUitpasLabels();
        }

        return $this->uitpasLabels;
    }

    private function initUitpasLabels()
    {
        $request = $this->httpClient->get((string)$this->labelsUrl);
        $response = $this->httpClient->send($request);

        if ($response->getStatusCode() === 200) {
            $this->uitpasLabels = json_decode($response->getBody(true), true);
        } else {
            $this->uitpasLabels = [];
        }
    }
}
