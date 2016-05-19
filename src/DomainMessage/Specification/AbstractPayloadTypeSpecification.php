<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\AnyOf;
use CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;

abstract class AbstractPayloadTypeSpecification implements SpecificationInterface
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function __construct()
    {
        $classes = (new SpecificationCollection());
        foreach ($this->validClassNames() as $className) {
            $classes = $classes->with(
                new PayloadIsInstanceOf($className)
            );
        }

        $this->specification = new AnyOf($classes);
    }

    /**
     * @return string[]
     */
    abstract protected function validClassNames();

    /**
     * @param DomainMessage $domainMessage
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return $this->specification->isSatisfiedBy($domainMessage);
    }
}
