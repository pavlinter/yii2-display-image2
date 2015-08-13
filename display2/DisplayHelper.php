<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.0.0
 */

namespace pavlinter\display2;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class DisplayHelper
 */
class DisplayHelper
{
    static $config;

    static $displayImage = 'pavlinter\display\DisplayImage';

    const CACHE_DIR = '@webroot/display-images-cache';

    const DEFAULT_CATEGORY = 'default';

    /**
     * @return null
     */
    public static function getConfig()
    {
        if (static::$config === null) {
            $definitions = Yii::$container->getDefinitions();
            if (isset($definitions[static::$displayImage])) {
                return $definitions[static::$displayImage];
            }
        }
        return static::$config;
    }


}
