<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.1.2
 */

namespace pavlinter\display2;

use Yii;
use yii\base\InvalidConfigException;

/**
 *
 */
class Module extends \yii\base\Module
{
    const VERSION = '2.1.2';

    public $componentId = 'display';

    /**
     * @var array|\Closure
     * example:
     * [
     *  'items' => [
     *    'imagesWebDir' => '@web/display-images/items',
     *    'imagesDir' => '@webroot/display-images/items',
     *    'defaultWebDir' => '@web/display-images/default',
     *    'defaultDir' => '@webroot/display-images/default',
     *  ],
     *  'all' => [
     *    'imagesWebDir' => '@web/display-images/images',
     *    'imagesDir' => '@webroot/display-images/images',
     *    'defaultWebDir' => '@web/display-images/default',
     *    'defaultDir' => '@webroot/display-images/default',
     *  ]
     *]
     */
    public $categories;
    /**
     * @var string FULL path to cache directory
     */
    public $cacheDir    = '@webroot/display-images-cache2';
    /**
     * @var string URL path to cache directory
     */
    public $cacheWebDir = '@web/display-images-cache2';
    /**
     * integer - rewrite image after seconds
     * null - disable rewrite image
     * 'auto' - rewrite image if modified file date is different
     * @var integer|null|string
     */
    public $cacheSeconds = 'auto';
    /**
     * @var string the default image class name when calling [[image()]] to create a new image.
     * @see imageConfig
     */
    public $imageClass = 'pavlinter\display2\objects\Image';

    /**
     * @var array|\Closure
     * @see imageClass
     */
    public $imageConfig = [];
    /**
     * @var integer max image resize for one request
     */
    public $maxResize = 20;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if ($this->categories instanceof \Closure) {
            $this->categories = call_user_func($this->categories);
        }
        if (empty($this->categories)) {
            throw new InvalidConfigException('The "categories" property must be set.');
        }
        parent::init();
    }
}
