<?php
namespace Alleingaenger\ImageProcessorBundle\Entity;

use JMS\Serializer\Annotation\Type;

class Point
{
    /**
     * @Type("int")
     * @var int
     */
    public $x;

    /**
     * @Type("int")
     * @var int
     */
    public $y;

    /**
     * @return \Imagine\Image\Point
     */
    public function toPoint()
    {
        return new \Imagine\Image\Point($this->x, $this->y);
    }
}