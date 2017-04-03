<?php

namespace Alleingaenger\ImageProcessorBundle\Controller;

use Alleingaenger\ImageProcessorBundle\Entity\MetaData;
use Alleingaenger\ImageProcessorBundle\Service\ImageProcessorService;
use Psr\Log\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController extends Controller
{
    /**
     * @Post("{type}", defaults={"type" = "jpeg"})
     * @param Request $request
     * @param string $type
     * @return MetaData[]
     */
    public function uploadAction(Request $request, $type)
    {
        if (empty($request->files->keys())) {
            throw new UploadException("No file is uploaded");
        }
        return $this->imageProcessorService()->saveRawImage($request->files->get($request->files->keys()[0]), $type);
    }

    /**
     * @Get("{name}.{type}", defaults={"type" = "jpeg"})
     * @param Request $request
     * @param string $name
     * @return Response
     */
    public function downloadImage(Request $request, $name)
    {
        $origMeta = $this->imageProcessorService()->getMeta($name);
        if (!$origMeta) {
            throw new NotFoundHttpException("No image with this name known");
        }
        $changeSets = [];
        foreach ($request->query->all() as $item) {
            try {
                $changeSet = $this->imageProcessorService()->deserializeChangeSet($item);
                $changeSets[] = $changeSet;
            } catch (\Exception $ignoredException) {
                throw new InvalidArgumentException("Wrong parameter");
            }
        }
        $currentMeta = $this->imageProcessorService()->changeImage($changeSets, $origMeta);
        return $this->redirect($this->publicDirectory() . '/' . $currentMeta->filename, 302);
    }

    /**
     * @Get
     * @param Request $request
     * @return \Alleingaenger\ImageProcessorBundle\Entity\MetaData[]
     */
    public function getVariantsMeta(Request $request)
    {
        $filter = $request->get('f') ?: '*';
        return $this->imageProcessorService()->getVariantsMeta($filter);
    }

    /**
     * @Delete
     * @param Request $request
     * @return MetaData[]
     */
    public function removeAll(Request $request)
    {
        $this->imageProcessorService()->removeAll();
        return $this->getVariantsMeta($request);
    }

    /**
     * @return ImageProcessorService
     */
    private function imageProcessorService()
    {
        return $this->get('alleingaenger_image_processor.imageprocessor');
    }

    /**
     * @return string
     */
    private function publicDirectory()
    {
        return $this->getParameter('alleingaenger_image_processor.publicDirectory');
    }
}
