<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 0.3.1
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;

/**
 * Class ResizeModeMin
 */
class ResizeModeMin extends \yii\base\Object implements ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage)
    {
        /* @var $size \Imagine\Image\Box */
        $size = $originalImage->getSize();
        if ($image->width) {
            if ($size->getWidth() >= $image->width) {
                $divider = $size->getWidth() / $image->width;
            } else {
                $divider = $image->width / $size->getWidth();
            }
            $h = $size->getHeight() / $divider;
            $w  = $image->width;
        } else if ($image->height) {
            if ($size->getHeight() >= $image->height) {
                $divider = $size->getHeight() / $image->height;
            } else {
                $divider = $image->height / $size->getHeight();
            }
            $w = $size->getWidth() / $divider;
            $h = $image->height;
        } else {
            $w = $size->getWidth();
            $h = $size->getHeight();
        }

        $Box = new Box($w, $h);
        $newImage = $originalImage->thumbnail($Box);
        $boxNew = $newImage->getSize();

        $x = ($Box->getWidth() - $boxNew->getWidth())/2;
        $y = ($Box->getHeight() - $boxNew->getHeight())/2;

        $point = new \Imagine\Image\Point($x, $y);
        $palette = new \Imagine\Image\Palette\RGB();
        $color = $palette->color($image->bgColor, $image->bgAlpha);

        return \yii\imagine\Image::getImagine()->create($Box, $color)->paste($newImage, $point);
    }
}
