<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class CdbXmlDocumentController
{
    /**
     * @var DocumentRepositoryInterface
     */
    protected $documentRepository;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     */
    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @param $cdbid
     * @return Response
     */
    public function get($cdbid)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');

        try {
            $document = $this->documentRepository->get($cdbid);

            if (is_null($document)) {
                $problem = new ApiProblem('The document could not be found.');
                $problem->setStatus(Response::HTTP_NOT_FOUND);
            }
        } catch (DocumentGoneException $e) {
            $problem = new ApiProblem('The document is gone.');
            $problem->setStatus(Response::HTTP_GONE);
        }

        if (isset($document)) {
            $response->setContent($document->getCdbXml());
        }

        if (isset($problem)) {
            $problem
                ->setDetail('A problem occurred when trying to show the document with id: ' .$cdbid)
                ->setType('about:blank');

            $response
                ->setContent($problem->asXml())
                ->setStatusCode($problem->getStatus());
        }

        return $response;
    }
}
