<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.2.0
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;
use yii\base\InvalidConfigException;


/**
 * Class ResizeModeMin
 */
class ResizeModeMinRestrict extends \yii\base\BaseObject implements \pavlinter\display2\objects\ResizeModeInterface
{
    /**
     * @param \pavlinter\display2\objects\Image $image
     * @param \Imagine\Gd\Image $originalImage
     * @return static
     * @throws InvalidConfigException
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

        $w = $image->width;  // if image smaller
        $h = $image->height; // if image smaller
        if ($wDivider > $hDivider) {
            if ($size->getHeight() >= $image->height) {
                $w = $size->getWidth() / $hDivider;
                $h = $image->height;
            }
        } else { //$wDivider <= $hDivider
            if ($size->getWidth() >= $image->width) {
                $w  = $image->width;
                $h = $size->getHeight() / $wDivider;
            }
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
