<?php
namespace Alleingaenger\ImageProcessorBundle\Entity;

use JMS\Serializer\Annotation\Type;

class ChangeSet
{
    /**
     * @Type("Alleingaenger\ImageProcessorBundle\Entity\Crop")
     * @var Crop
     */
    public $crop;

    /**
     * @Type("int")
     * @var int
     */
    public $rotate;

    /**
     * @Type("Alleingaenger\ImageProcessorBundle\Entity\Box")
     * @var Box
     */
    public $scale;
}