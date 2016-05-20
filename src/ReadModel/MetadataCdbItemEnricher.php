<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use InvalidArgumentException;
use ValueObjects\Number\Natural;

class MetadataCdbItemEnricher implements MetadataCdbItemEnricherInterface
{
    /**
     * @var DateFormatterInterface
     */
    private $dateFormatter;

    /**
     * @param DateFormatterInterface $dateFormatter
     */
    public function __construct(DateFormatterInterface $dateFormatter)
    {
        $this->dateFormatter = $dateFormatter;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param Metadata $metadata
     * @return \CultureFeed_Cdb_Item_Base
     * @throws InvalidArgumentException
     */
    public function enrich(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        Metadata $metadata
    ) {
        $metadata = $metadata->serialize();

        if (isset($metadata['request_time'])) {
            $cdbItem = $this->enrichTime(
                $cdbItem,
                new Natural($metadata['request_time'])
            );
        }

        if (isset($metadata['user_nick']) && empty($cdbItem->getCreatedBy())) {
            $cdbItem->setCreatedBy($metadata['user_nick']);
        }

        if (isset($metadata['user_email'])) {
            $cdbItem->setLastUpdatedBy($metadata['user_email']);
        }

        if (isset($metadata['id'])) {
            $cdbItem->setExternalUrl($metadata['id']);
        } else {
            throw new InvalidArgumentException('The metadata does not contain the "id" property required to locate the item.');
        }

        return $cdbItem;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param Natural $requestTime
     * @return \CultureFeed_Cdb_Item_Base
     */
    public function enrichTime(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        Natural $requestTime
    ) {
        if (!empty($requestTime)) {
            if (empty($cdbItem->getCreationDate())) {
                $cdbItem->setCreationDate(
                    $this->dateFormatter->format($requestTime->toNative())
                );
            }
            $cdbItem->setLastUpdated(
                $this->dateFormatter->format($requestTime->toNative())
            );
        }

        return $cdbItem;
    }
}
