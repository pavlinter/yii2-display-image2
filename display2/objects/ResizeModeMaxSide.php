<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2018
 * @package yii2-display-image2
 * @version 2.3.4
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;

/**
 * Class ResizeModeMax
 * Max only one side
 */
class ResizeModeMaxSide extends \yii\base\BaseObject implements ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage)
    {
        if (empty($image->width) && empty($image->height)) {
            throw new InvalidConfigException('The "width" or "height" property must be set for "' . $image::className() . '".');
        } elseif ($image->width && $image->height) {
            throw new InvalidConfigException('Must be set "width" or "height" property for "' . $image::className() . '".');
        }

        /* @var $size \Imagine\Image\Box */
        $size = $originalImage->getSize();

        $wDivider = 0;
        $hDivider = 0;

        if ($image->width) {
            $wDivider = $size->getWidth() >= $image->width ? $size->getWidth() / $image->width : 0;
        } elseif ($image->height) {
            $hDivider = $size->getHeight() >= $image->height ? $size->getHeight() / $image->height : 0;
        }
        
        if ($wDivider > $hDivider) {
            $w  = $image->width;
            $h  = $size->getHeight() / $wDivider;
        } else if ($hDivider > $wDivider) {
            $w = $size->getWidth() / $hDivider;
            $h = $image->height;
        } else {
            return $originalImage;
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
