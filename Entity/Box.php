<?php
namespace Alleingaenger\ImageProcessorBundle\Entity;

use JMS\Serializer\Annotation\Type;

class Box
{
    /**
     * @Type("int")
     * @var int
     */
    public $width;

    /**
     * @Type("int")
     * @var int
     */
    public $height;

    /**
     * @return \Imagine\Image\Box
     */
    public function toBox()
    {
        return new \Imagine\Image\Box($this->width, $this->height);
    }
}