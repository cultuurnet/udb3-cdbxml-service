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
        $metadataArray = $metadata->serialize();

        $cdbItem = $this->enrichTime($cdbItem, $metadata);

        $this->setCreatedBy($cdbItem, $metadataArray);

        $this->setLastUpdatedBy($cdbItem, $metadataArray);

        if (isset($metadataArray['id'])) {
            $cdbItem->setExternalUrl($metadataArray['id']);
        } else {
            throw new InvalidArgumentException('The metadata does not contain the "id" property required to locate the item.');
        }

        return $cdbItem;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param Metadata $metadata
     * @return \CultureFeed_Cdb_Item_Base
     */
    public function enrichTime(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        Metadata $metadata
    ) {
        $metadataArray = $metadata->serialize();

        if (isset($metadataArray['request_time'])) {

            $requestTime = new Natural($metadataArray['request_time']);

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

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param string[] $metadataArray
     */
    private function setCreatedBy(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        array $metadataArray
    ): void {
        if (!empty($cdbItem->getCreatedBy())) {
            return;
        }

        if (isset($metadataArray['user_id'])) {
            $cdbItem->setCreatedBy($metadataArray['user_id']);
        } else if (isset($metadataArray['user_nick'])) {
            $cdbItem->setCreatedBy($metadataArray['user_nick']);
        }
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param string[] $metadataArray
     */
    private function setLastUpdatedBy(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        array $metadataArray
    ): void {
        if (isset($metadataArray['user_id'])) {
            $cdbItem->setLastUpdatedBy($metadataArray['user_id']);
        } else if (isset($metadataArray['user_email'])) {
            $cdbItem->setLastUpdatedBy($metadataArray['user_email']);
        }
    }
}
