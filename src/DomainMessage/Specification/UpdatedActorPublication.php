<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\AnyOf;
use CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;

class UpdatedActorPublication implements SpecificationInterface
{
    /**
     * A hardcoded list of namespaced event class-names that trigger an actor publication.
     *
     * @var string[]
     */
    protected $classNames = [
        OrganizerUpdatedFromUDB2::class,
    ];

    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function __construct()
    {
        $classes = (new SpecificationCollection());
        foreach ($this->classNames as $className) {
            $classes = $classes->with(
                new PayloadIsInstanceOf($className)
            );
        }

        $this->specification = new AnyOf($classes);
    }

    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return $this->specification->isSatisfiedBy($domainMessage);
    }
}
