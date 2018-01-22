<?php
/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.2.0
 */

namespace pavlinter\display2\controllers;

use pavlinter\display2\Module;
use Yii;
use yii\web\Controller;

/**
 * Class ImageController
 */
class ImageController extends Controller
{
    public function actionCrop()
    {
        $imageConfig = [
            'id_row' => Yii::$app->request->get('id_row'),
            'width' => Yii::$app->request->get('width'),
            'height' => Yii::$app->request->get('height'),
            'image' => Yii::$app->request->get('image'),
            'category' => Yii::$app->request->get('category'),
            'bgColor' => Yii::$app->request->get('bgColor'),
            'bgAlpha' => Yii::$app->request->get('bgAlpha'),
            'mode' => Yii::$app->request->get('mode'),
        ];

        foreach ($imageConfig as $k => $v) {
            if ($v === null) {
                unset($imageConfig[$k]);
            }
        }
        /* @var $display \pavlinter\display2\components\Display */
        $display = Yii::$app->get(Module::getInstance()->componentId);
        $image = $display->getImage($imageConfig);

        $img = \yii\imagine\Image::getImagine()->open($image->rootSrc);
        $ext = pathinfo($image->rootSrc ,PATHINFO_EXTENSION);
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext));
        $img->show($ext, ['jpeg_quality' => 100]);
        return;
    }
}