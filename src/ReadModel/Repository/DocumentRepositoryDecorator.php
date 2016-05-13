<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

abstract class DocumentRepositoryDecorator implements DocumentRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    protected $decoratedRepository;

    public function __construct(DocumentRepositoryInterface $repository)
    {
        $this->decoratedRepository = $repository;
    }

    public function get($id)
    {
        return $this->decoratedRepository->get($id);
    }

    public function save(CdbXmlDocument $readModel)
    {
        $this->decoratedRepository->save($readModel);
    }

    public function remove($id)
    {
        $this->decoratedRepository->remove($id);
    }
}
