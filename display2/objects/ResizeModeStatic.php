<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.3.1
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;

/**
 * Class ResizeModeStatic
 */
class ResizeModeStatic extends \yii\base\BaseObject implements ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage)
    {
        $Box = new Box($image->width, $image->height);
        $newImage = $originalImage->thumbnail($Box);
        $boxNew = $newImage->getSize();

        $x = ($Box->getWidth() - $boxNew->getWidth())/2;
        $y = ($Box->getHeight() - $boxNew->getHeight())/2;

        $point = new \Imagine\Image\Point($x,$y);
        $palette = new \Imagine\Image\Palette\RGB();
        $color = $palette->color($image->bgColor, $image->bgAlpha);

        return \yii\imagine\Image::getImagine()->create($Box, $color)->paste($newImage, $point);
    }
}
