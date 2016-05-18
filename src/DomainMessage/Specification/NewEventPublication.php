<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\AnyOf;
use CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2Event;

class NewEventPublication implements SpecificationInterface
{
    /**
     * A hardcoded list of namespaced event class-names that trigger a new event publication.
     *
     * @var string[]
     */
    protected $classNames = [
        PlaceCreated::class,
        PlaceImportedFromUDB2::class,
        PlaceImportedFromUDB2Event::class,
        EventImportedFromUDB2::class,
        EventCreated::class,
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
