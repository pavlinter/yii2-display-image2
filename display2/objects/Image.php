<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.1.0
 */

namespace pavlinter\display2\objects;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use Imagine\Image\ManipulatorInterface;

/**
 * Class Image
 *
 * @property string originImage
 */
class Image extends \yii\base\BaseObject
{
    const MODE_INSET    = ManipulatorInterface::THUMBNAIL_INSET;
    const MODE_OUTBOUND = ManipulatorInterface::THUMBNAIL_OUTBOUND;
    const MODE_STATIC   = 'static';
    const MODE_MIN = 'min';
    const MODE_MIN_RESTRICT = 'min';

    /**
     * @var integer id from db
     */
    public $id_row;

    /**
     * @var string new image name after resize
     */
    public $name;

    /**
     * @var integer image width
     */
    public $width;

    /**
     * @var integer image height
     */
    public $height;

    /**
     * @var integer image path
     */
    public $image;

    /**
     * @var string the image category
     */
    public $category;

    /**
     * @var string the default image directory (work if enabled [[innerCacheDir]])
     */
    public $defaultCategory = 'default';

    /**
     * @var string general default pictures for all category (work if enabled [[innerCacheDir]])
     */
    public $generalDefaultDir = true;

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

    /**
     * @var array html options
     */
    public $options = [];

    /**
     * @var boolean return absolute path
     */
    public $absolutePath = false;

    /**
     * @var string [[DisplayImage::MODE_INSET || DisplayImage::MODE_OUTBOUND || DisplayImage::MODE_STATIC || DisplayImage::MODE_MIN]]
     * or create own resize [[resize]]
     */
    public $mode;

    /**
     * @var callable encode new image name
     */
    public $encodeName;

    /**
     * @var string the url to images directory
     */
    public $imagesWebDir;

    /**
     * @var string the path to images directory
     */
    public $imagesDir;

    /**
     * @var string the url where default image
     */
    public $defaultWebDir;

    /**
     * @var string the path where default image
     */
    public $defaultDir;

    /**
     * @var string the name default image
     */
    public $defaultImage = 'default.png';

    /**
     * @var string|\Closure generate size directory name
     */
    public $sizeDirectory;

    /**
     * @var string
     */
    public $src;

    /**
     * @var string
     */
    public $rootSrc;

    /**
     * @var string
     */
    private $_idRowPath = '';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (empty($this->imagesWebDir)) {
            throw new InvalidConfigException('The "imagesWebDir" property must be set for "' . $this->imagesWebDir . '".');
        }
        if (empty($this->imagesDir)) {
            throw new InvalidConfigException('The "imagesDir" property must be set for "' . $this->imagesDir . '".');
        }
        if (empty($this->defaultWebDir)) {
            throw new InvalidConfigException('The "defaultWebDir" property must be set for "' . $this->defaultWebDir . '".');
        }
        if (empty($this->defaultDir)) {
            throw new InvalidConfigException('The "defaultDir" property must be set for "' . $this->defaultDir . '".');
        }

        $this->image            = ltrim($this->image, '/');
        $this->imagesDir        = Yii::getAlias(rtrim($this->imagesDir, '/')) . '/';
        $this->imagesWebDir     = Yii::getAlias(rtrim($this->imagesWebDir, '/')) . '/';
        $this->defaultDir       = Yii::getAlias(rtrim($this->defaultDir, '/')) . '/';
        $this->defaultWebDir    = Yii::getAlias(rtrim($this->defaultWebDir, '/')) . '/';

        if ($this->mode !== self::MODE_MIN) {
            if ($this->width && !$this->height) {
                $this->height = $this->width;
            } elseif(!$this->width && $this->height) {
                $this->width = $this->height;
            }
        }

        if ($this->sizeDirectory === null) {
            $this->sizeDirectory = $this->width . 'x' . $this->height . '_' . $this->mode . '_' . $this->bgColor . '_' . $this->bgAlpha;
        } else if ($this->sizeDirectory  instanceof \Closure) {
            $this->sizeDirectory = call_user_func($this->sizeDirectory, $this);
        }
        $this->sizeDirectory = $this->sizeDirectory . '/' ;

        if ($this->id_row) {
            FileHelper::createDirectory($this->imagesDir . $this->id_row);
            $this->_idRowPath =  $this->id_row . '/';
        }
    }

    public function appendTimestamp()
    {
        $timestamp = @filemtime($this->rootSrc);
        if ($timestamp > 0) {
            $this->src .= "?v=" . $timestamp;
        }
    }

    /**
     * @return string
     */
    public function getOriginImage()
    {
        return $this->imagesWebDir . $this->getIdRowPath() . $this->image;
    }

    /**
     * @return string
     */
    public function getIdRowPath()
    {
        return $this->_idRowPath;
    }
}
