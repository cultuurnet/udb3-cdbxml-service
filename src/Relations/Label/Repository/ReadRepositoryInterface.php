<?php

namespace CultuurNet\UDB3\CdbXmlService\Relations\Label\Repository;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\StringLiteral\StringLiteral;

interface ReadRepositoryInterface
{
    /**
     * @param LabelName $labelName
     * @return \Generator|LabelRelation[]
     */
    public function getLabelRelations(LabelName $labelName);

    /**
     * @param StringLiteral $relationId
     * @return LabelRelation[]
     */
    public function getLabelRelationsForItem(StringLiteral $relationId);
}
