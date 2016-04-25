<?php

namespace CultuurNet\UDB3\CdbXmlService\Media;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultureFeed_Cdb_Data_EventDetail;
use CultureFeed_Cdb_Data_Media;
use CultureFeed_Cdb_Item_Base;
use CultureFeed_Cdb_Data_File;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractMainImageSelected;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

trait EditImageTrait
{
    protected $imageTypes = [
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO,
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_IMAGEWEB,
    ];

    /**
     * Delete a given index on the cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function removeImageFromCdbItem(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $oldMedia = $this->getCdbItemMedia($cdbItem);

        $newMedia = new CultureFeed_Cdb_Data_Media();
        foreach ($oldMedia as $key => $file) {
            if (!$this->fileMatchesMediaObject($file, $image->getMediaObjectId())) {
                $newMedia->add($file);
            }
        }

        $images = $newMedia->byMediaTypes($this->imageTypes);
        if ($images->count() > 0) {
            $images->rewind();
            $images->current()->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO);
        }

        $details = $cdbItem->getDetails();
        $details->rewind();
        $details->current()->setMedia($newMedia);
    }

    /**
     * Select the main image for a cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function selectCdbItemMainImage(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $media = $this->getCdbItemMedia($cdbItem);
        $mainImageId = $image->getMediaObjectId();

        $mainImages = $media->byMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO);
        if ($mainImages->count() > 0) {
            $mainImages->rewind();
            $mainImages->current()->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_IMAGEWEB);
        }

        foreach ($media as $file) {
            if ($this->fileMatchesMediaObject($file, $mainImageId)) {
                $file->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO);
            }
        }
    }

    /**
     * Update an existing image on the cdb item.
     *
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param UUID $mediaObjectId
     * @param StringLiteral $description
     * @param StringLiteral $copyrightHolder
     */
    protected function updateImageOnCdbItem(
        CultureFeed_Cdb_Item_Base $cdbItem,
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ) {
        $media = $this->getCdbItemMedia($cdbItem);

        foreach ($media as $file) {
            if ($this->fileMatchesMediaObject($file, $mediaObjectId)) {
                $file->setTitle((string) $description);
                $file->setCopyright((string) $copyrightHolder);
            }
        }
    }


    /**
     * Add an image to the cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function addImageToCdbItem(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $sourceUri = (string) $image->getSourceLocation();
        $uriParts = explode('/', $sourceUri);
        $media = $this->getCdbItemMedia($cdbItem);

        $file = new CultureFeed_Cdb_Data_File();
        $file->setMain();
        $file->setHLink($sourceUri);

        // If there are no existing images the newly added one should be main.
        if ($media->byMediaTypes($this->imageTypes)->count() === 0) {
            $file->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO);
        } else {
            $file->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_IMAGEWEB);
        }

        // If the file name does not contain an extension, default to jpeg.
        $extension = 'jpeg';

        // If the file name does contain an extension, then normalize it.
        $filename = end($uriParts);
        if (false !== strpos($filename, '.')) {
            $fileparts = explode('.', $filename);
            $extension = strtolower(end($fileparts));
            if ($extension === 'jpg') {
                $extension = 'jpeg';
            }
        }

        $file->setFileType($extension);
        $file->setFileName($filename);

        $file->setCopyright((string) $image->getCopyrightHolder());
        $file->setTitle((string) $image->getDescription());

        $media->add($file);
    }

    /**
     * Get the media for a CDB item.
     *
     * If the items does not have any detials, one will be created.
     *
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     *
     * @return CultureFeed_Cdb_Data_Media
     */
    protected function getCdbItemMedia(CultureFeed_Cdb_Item_Base $cdbItem)
    {
        $details = $cdbItem->getDetails();
        $details->rewind();

        // Get the first detail.
        $detail = null;
        foreach ($details as $languageDetail) {
            if (!$detail) {
                $detail = $languageDetail;
            }
        }

        // Make sure a detail exists.
        if (empty($detail)) {
            $detail = new CultureFeed_Cdb_Data_EventDetail();
            $details->add($detail);
        }

        $media = $detail->getMedia();
        $media->rewind();
        return $media;
    }

    /**
     * @param CultureFeed_Cdb_Data_File $file
     * @param UUID $mediaObjectId
     * @return bool
     */
    protected function fileMatchesMediaObject(
        CultureFeed_Cdb_Data_File $file,
        UUID $mediaObjectId
    ) {
        // Matching against the CDBID in the name of the image because
        // that's the only reference in UDB2 we have.
        return !!strpos($file->getHLink(), (string) $mediaObjectId);
    }

    /**
     * Apply the imageAdded event.
     * @param AbstractImageAdded $imageAdded
     * @param MetaData $metadata
     */
    protected function applyImageAdded(
        AbstractImageAdded $imageAdded,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($imageAdded->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $this->addImageToCdbItem($event, $imageAdded->getImage());

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * Apply the imageUpdated event to udb2.
     * @param AbstractImageUpdated $mageUpdated
     * @param MetaData $metadata
     */
    protected function applyImageUpdated(
        AbstractImageUpdated $mageUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($mageUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $this->updateImageOnCdbItem(
            $event,
            $mageUpdated->getMediaObjectId(),
            $mageUpdated->getDescription(),
            $mageUpdated->getCopyrightHolder()
        );

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractImageRemoved $imageRemoved
     * @param Metadata $metadata
     */
    protected function applyImageRemoved(
        AbstractImageRemoved $imageRemoved,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($imageRemoved->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $this->removeImageFromCdbItem($event, $imageRemoved->getImage());

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractMainImageSelected $mainImageSelected
     * @param Metadata $metadata
     */
    protected function applyMainImageSelected(
        AbstractMainImageSelected $mainImageSelected,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($mainImageSelected->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $this->selectCdbItemMainImage($event, $mainImageSelected->getImage());

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }
}