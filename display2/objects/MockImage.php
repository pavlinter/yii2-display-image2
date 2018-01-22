<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.2.0
 */

namespace pavlinter\display2\objects;

/**
 *  Class MockImage
 *
 *  $resizeMode = Yii::createObject(['class' => 'pavlinter\display2\objects\ResizeModeMinRestrict']);
 *  $img = \yii\imagine\Image::getImagine()->open($pathToImage);
 *  $image = Yii::createObject([
 *      'class' => 'pavlinter\display2\objects\MockImage',
 *      'width' => 800,
 *      'height' => 600,
 *      'bgColor' => 'ff0000',
 *  ]);
 *  $croppedImg = $resizeMode->resize($image, $img);
 *  $croppedImg->save($ownPath)
 */
class MockImage extends Image
{
    /**
     * @var integer image width
     */
    public $width;

    /**
     * @var integer image height
     */
    public $height;

    /**
     * @var string the background color for [[DisplayImage::MODE_STATIC]] or [[DisplayImage::MODE_MIN]] or [[resize]]
     * default white value
     */
    public $bgColor = '000000';

    /**
     * @var integer the background transparent for [[DisplayImage::MODE_STATIC]] or [[DisplayImage::MODE_MIN]] or [[resize]]
     * default 100 value (not transparent)
     * range 0 - 100
     */
    public $bgAlpha = 100;


    public function init()
    {

    }
}
