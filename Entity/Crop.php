<?php
namespace Alleingaenger\ImageProcessorBundle\Entity;

use JMS\Serializer\Annotation\Type;

class Crop
{
    /**
     * @Type("Alleingaenger\ImageProcessorBundle\Entity\Point")
     * @var Point
     */
    public $start;

    /**
     * @Type("Alleingaenger\ImageProcessorBundle\Entity\Box")
     * @var Box
     */
    public $box;
}