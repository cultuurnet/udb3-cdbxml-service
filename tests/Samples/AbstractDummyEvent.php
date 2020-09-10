<?php

namespace CultuurNet\UDB3\CdbXmlService\Samples;

use Broadway\Serializer\SerializableInterface;

class AbstractDummyEvent implements SerializableInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @param string $id
     */
    final public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new static(
            $data['id']
        );
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'id' => $this->id,
        ];
    }
}
