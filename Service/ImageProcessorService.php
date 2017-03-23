<?php
namespace Alleingaenger\ImageProcessorBundle\Service;

use Alleingaenger\ImageProcessorBundle\Entity\ChangeSet;
use Alleingaenger\ImageProcessorBundle\Entity\Crop;
use Alleingaenger\ImageProcessorBundle\Entity\MetaData;
use Imagine\Gd\Imagine;
use Alleingaenger\ImageProcessorBundle\Entity\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Alleingaenger\ImageProcessorBundle\Entity\Point;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\File\File;
use JMS\Serializer\Serializer;

class ImageProcessorService
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $uploadDir;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $saveOptions = [
        'jpeg_quality' => 90,
        'png_compression_level' => 9,
    ];

    public function __construct($uploadDir, $redisConf)
    {
        $this->imagine = new Imagine();
        $port = isset($redisConf['port']) ? $redisConf['port'] : 6379;
        $this->redis = $this->setUpRedis($redisConf['host'], $port);
        $this->uploadDir = $uploadDir;
        $this->serializer = SerializerBuilder::create()->build();
    }

    /**
     * @param string $changeSetJson
     * @return ChangeSet
     */
    public function getChangeSet($changeSetJson)
    {
        return $this->serializer->deserialize($changeSetJson, ChangeSet::class, 'json');
    }

    /**
     * @param File $image
     * @param string $type
     * @return string
     */
    public function saveRawImage($image, $type)
    {
        $name = $this->generateName();
        $size = $this->imagine->open($image->getPathname())->getSize();
        $meta = new MetaData(
            $size->getWidth(),
            $size->getHeight(),
            $name . '.' . $type,
            str_replace('/', '', $name),
            $type,
            ''
        );
        mkdir(dirname($this->uploadDir . '/' . $meta->filename), 0777, true);
        $image->move(dirname($this->uploadDir . '/' . $meta->filename), basename($meta->filename));
        $this->redis->set($meta->orig, $this->serializer->serialize($meta, 'json'));
        return $meta->orig;
    }

    /**
     * @param ChangeSet[] $changeSets
     * @param MetaData $originalMeta
     * @return MetaData
     */
    public function changeImage($changeSets, MetaData $originalMeta)
    {
        $changeSetsQuery = $this->buildChangeSetsQuery($changeSets);
        $key = $originalMeta->orig . ($changeSetsQuery !== '' ? '?' . md5($changeSetsQuery) : '');
        $meta = $this->getMeta($key);
        if ($meta) {
            return $meta;
        }
        $imageHandle = $this->imagine->open($this->uploadDir . '/' . $originalMeta->filename);
        foreach ($changeSets as $changeSet) {
            $imageHandle = $this->applyChangeSetToImage($imageHandle, $changeSet);
        }
        $size = $imageHandle->getSize();
        $meta = new MetaData(
            $size->getWidth(),
            $size->getHeight(),
            $this->generateName() . '.' . $originalMeta->type,
            $originalMeta->orig,
            $originalMeta->type,
            $changeSetsQuery
        );
        mkdir(dirname($this->uploadDir . '/' . $meta->filename), 0777, true);
        $imageHandle->save($this->uploadDir . '/' . $meta->filename, $this->saveOptions);
        $this->redis->set($key, $this->serializer->serialize($meta, 'json'));
        return $meta;
    }

    /**
     * @param string $name
     * @return MetaData[]
     */
    public function getVariantsMeta($name)
    {
        $keys = $this->redis->keys($name . '*');
        $meta = [];
        foreach ($keys as $key) {
            $meta[$key] = $this->getMeta($key);
        }
        return $meta;
    }

    /**
     * @param string $key
     * @return MetaData
     */
    public function getMeta($key)
    {
        $value = $this->redis->get($key);
        if (!$value) {
            return null;
        }
        return $this->serializer->deserialize($value, MetaData::class, 'json');
    }

    /**
     * @param $chnageSetJson
     * @return ChangeSet
     */
    public function deserializeChangeSet($chnageSetJson)
    {
        return $this->serializer->deserialize($chnageSetJson, ChangeSet::class, 'json');
    }

    public function removeAll()
    {
        /** @var MetaData $key */
        foreach ($this->redis->keys('*') as $key) {
            try {
                unlink($this->uploadDir . '/' . $this->getMeta($key)->filename);
            } catch (\Exception $e) {}
            $this->redis->delete($key);
        }
    }

    /**
     * @param ImageInterface $imageHandle
     * @param ChangeSet $changeSet
     * @return ImageInterface|static
     */
    private function applyChangeSetToImage(ImageInterface $imageHandle, ChangeSet $changeSet)
    {
        if ($this->validateCrop($changeSet->crop)) {
            $imageHandle = $imageHandle->crop($changeSet->crop->start->toPoint(), $changeSet->crop->box->toBox());
        }
        if ($changeSet->rotate !== null) {
            $imageHandle = $imageHandle->rotate($changeSet->rotate % 360);
        }
        if ($this->validateBox($changeSet->scale)) {
            $imageHandle = $imageHandle->resize($changeSet->scale->toBox());
        }
        return $imageHandle;
    }

    /**
     * @param ChangeSet[] $changeSets
     * @return string
     */
    private function buildChangeSetsQuery($changeSets)
    {
        $jsonArr = [];
        $i = 1;
        foreach ($changeSets as $set) {
            $jsonArr[] = dechex($i) . '=' . $this->serializer->serialize($set, 'json');
            $i++;
        }
        return join('&', $jsonArr);
    }

    /**
     * @param Crop|null $crop
     * @return bool
     */
    private function validateCrop(Crop $crop = null)
    {
        return $crop !== null
            && $crop->start !== null && $crop->start->x !== null && $crop->start->y !== null
            && $this->validateBox($crop->box)
            ;
    }

    /**
     * @param Box|null $box
     * @return bool
     */
    private function validateBox($box = null)
    {
        return $box !== null && $box->width !== null && $box->height !== null;
    }

    /**
     * @param string $host
     * @param int $port
     * @return \Redis
     */
    private function setUpRedis($host, $port)
    {
        $redis = new \Redis();
        $redis->pconnect($host, $port);
        return $redis;
    }

    /**
     * @return string
     */
    private function generateName()
    {
        $name = (rand() << 16) | time();

        // Shuffle the bits of the two numbers to keep the timestamp but obfuscate it a little bit
        $tmp = ($name ^ ($name >> 16)) & 0x00000000ffff0000;
        $name ^= ($tmp ^ ($tmp << 16));
        $tmp = ($name ^ ($name >> 8)) & 0x0000ff000000ff00;
        $name ^= ($tmp ^ ($tmp << 8));
        $tmp = ($name ^ ($name >> 4)) & 0x00f000f000f000f0;
        $name ^= ($tmp ^ ($tmp << 4));
        $tmp = ($name ^ ($name >> 2)) & 0x0c0c0c0c0c0c0c0c;
        $name ^= ($tmp ^ ($tmp << 2));
        $tmp = ($name ^ ($name >> 1)) & 0x2222222222222222;
        $name ^= ($tmp ^ ($tmp << 1));

        $path = rand();

        return sprintf(
            '%02x/%02x/%02x/%016x',
            $path >> 16 & 0xff,
            $path >> 8 & 0xff,
            $path & 0xff,
            $name
        );
    }
}