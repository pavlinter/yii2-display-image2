<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2018
 * @package yii2-display-image2
 * @version 2.2.0
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;

/**
 * Class ResizeModeMax
 */
class ResizeModeMax extends \yii\base\BaseObject implements ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage)
    {
        if (empty($image->width)) {
            throw new InvalidConfigException('The "width" property must be set for "' . $image::className() . '".');
        }
        if (empty($image->height)) {
            throw new InvalidConfigException('The "height" property must be set for "' . $image::className() . '".');
        }

        /* @var $size \Imagine\Image\Box */
        $size = $originalImage->getSize();

        $wDivider = $size->getWidth() >= $image->width ? $size->getWidth() / $image->width : 0;
        $hDivider = $size->getHeight() >= $image->height ? $size->getHeight() / $image->height : 0;

        if (!$wDivider && !$wDivider) {
            return $originalImage;
        } else if ($wDivider > $hDivider) {
            $w  = $image->width;
            $h = $size->getHeight() / $wDivider;
        } else { //$wDivider <= $hDivider
            $w = $size->getWidth() / $hDivider;
            $h = $image->height;
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
