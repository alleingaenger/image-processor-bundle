<?php
namespace Alleingaenger\ImageProcessorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DemoController extends Controller
{
    /**
     * @Get("/demo/upload")
     * @return string
     */
    public function uploadDemoAction()
    {
        $url = 'http://localhost/loader/jpeg';

        $fields = ['name' => new \CurlFile(__DIR__ . '/1_square.jpg', 'image/png', 'filename.png')];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec( $ch );
        return json_decode($result, true);
    }

    /**
     * @Get("/demo/download")
     * @param Request $request
     * @return RedirectResponse
     */
    public function downloadDemoAction(Request $request)
    {
        $url = 'http://localhost/loader';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $uploadedImages = array_keys(json_decode($result, true));

        $changeSets = [
            'a' => '{"rotate":90}',
            'b' => '{"scale":{"width":250,"height":500}}',
        ];
        $query = '?';
        foreach ($changeSets as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }

        $url = $request->getSchemeAndHttpHost().'/loader/' . $uploadedImages[count($uploadedImages)-1] . $query;

        echo '<a href="' . $url . '">' . urldecode($url) . '</a>'; exit;
    }

    /**
     * @Get("/demo/info")
     */
    public function infoDemoAction()
    {
        $url = 'http://localhost/loader';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec( $ch );
        return json_decode($result, true);
    }

    /**
     * @Get("/demo/clear")
     */
    public function clearDemoAction()
    {
        $url = 'http://localhost/loader/';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec( $ch );
        return json_decode($result, true);
    }
}