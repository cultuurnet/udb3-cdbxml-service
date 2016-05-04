<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use InvalidArgumentException;

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
            if (empty($cdbItem->getCreationDate())) {
                $cdbItem->setCreationDate(
                    $this->dateFormatter->format((int) $metadata['request_time'])
                );
            }
            $cdbItem->setLastUpdated(
                $this->dateFormatter->format((int) $metadata['request_time'])
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
}
