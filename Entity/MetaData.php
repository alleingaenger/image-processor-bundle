<?php
namespace Alleingaenger\ImageProcessorBundle\Entity;

use JMS\Serializer\Annotation\Type;

class MetaData
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
     * @Type("string")
     * @var string
     */
    public $filename;

    /**
     * @Type("string")
     * @var string
     */
    public $orig;

    /**
     * @Type("string")
     * @var string
     */
    public $type;

    /**
     * @Type("string")
     * @var string
     */
    public $changeSetQuery;

    /**
     * MetaData constructor.
     * @param int $width
     * @param int $height
     * @param string $filename
     * @param string $orig
     * @param string $type
     * @param string $changeSetQuery
     */
    public function __construct($width, $height, $filename, $orig, $type, $changeSetQuery)
    {
        $this->width = $width;
        $this->height = $height;
        $this->filename = $filename;
        $this->orig = $orig;
        $this->type = $type;
        $this->changeSetQuery = $changeSetQuery;
    }
}