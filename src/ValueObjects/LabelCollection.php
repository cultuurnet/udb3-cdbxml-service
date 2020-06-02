<?php

namespace CultuurNet\UDB3\CdbXmlService\ValueObjects;

use CultureFeed_Cdb_Data_Keyword;
use CultuurNet\UDB3\Label;

class LabelCollection implements \Countable
{
    /**
     * @var Label[]
     */
    private $labels;

    /**
     * @param Label[] $labels
     */
    public function __construct(array $labels = [])
    {
        array_walk(
            $labels,
            function ($item) {
                if (!$item instanceof Label) {
                    throw new \InvalidArgumentException(
                        'Argument $labels should only contain members of type Label'
                    );
                }
            }
        );

        $this->labels = array_values($labels);
    }

    /**
     * @param Label $label
     * @return LabelCollection
     */
    public function with(Label $label)
    {
        if (!$this->contains($label)) {
            $labels = array_merge($this->labels, [$label]);
        } else {
            $labels = $this->labels;
        }

        return new LabelCollection($labels);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->labels);
    }

    /**
     * @param Label $label
     * @return bool
     */
    private function contains(Label $label)
    {
        $equalLabels = array_filter(
            $this->labels,
            function (Label $existingLabel) use ($label) {
                return $label->equals($existingLabel);
            }
        );

        return !empty($equalLabels);
    }

    /**
     * @return Label[]
     */
    public function asArray()
    {
        return $this->labels;
    }

    /**
     * @param string[] $strings
     * @return LabelCollection
     */
    public static function fromStrings(array $strings)
    {
        $labelCollection = new LabelCollection();

        foreach ($strings as $string) {
            try {
                $label = new Label($string);
                $labelCollection = $labelCollection->with($label);
            } catch (\InvalidArgumentException $exception) {
            }
        }

        return $labelCollection;
    }
}
