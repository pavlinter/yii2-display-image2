<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.3.2
 */

namespace pavlinter\display2\objects;

use Imagine\Image\Box;
use Yii;

/**
 * Class ResizeMode
 */
class ResizeMode extends \yii\base\BaseObject implements ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage)
    {
        return $originalImage->thumbnail(new Box($image->width, $image->height), $image->mode);
    }
}
