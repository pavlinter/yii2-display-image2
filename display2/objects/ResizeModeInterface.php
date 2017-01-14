<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 0.3.1
 */

namespace pavlinter\display2\objects;

use Yii;

/**
 * ResizeModeInterface is the interface that should be implemented by ResizeMode
 */
interface ResizeModeInterface
{
    /**
     * @param $image \pavlinter\display2\objects\Image
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     */
    public function resize($image, $originalImage);
}
