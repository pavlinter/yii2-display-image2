<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.0.0
 */

namespace pavlinter\display2;

use Imagine\Image\Box;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;

/**
 * Class Display
 */
class Display extends \yii\base\Component
{
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
    public $cacheDir    = '@webroot/display-images-cache';
    /**
     * @var string URL path to cache directory
     */
    public $cacheWebDir = '@web/display-images-cache';
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
    public $imageClass = 'pavlinter\display2\Image';

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
     * @var integer already resized
     */
    private $_maxResized = 0;

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


    /**
     * @param array $config
     * @return \pavlinter\display2\Image
     * @throws InvalidConfigException
     */
    public function createImage($config = [])
    {
        $imageConfig = $this->imageConfig;
        if ($imageConfig instanceof \Closure) {
            $imageConfig = call_user_func($imageConfig, $config);
        }
        if (!isset($imageConfig['class'])) {
            $imageConfig['class'] = $this->imageClass;
        }
        return Yii::createObject(ArrayHelper::merge($imageConfig, $config));
    }

    /**
     * @param array $imageConfig
     * @return \pavlinter\display2\Image
     * @throws \yii\base\Exception
     */
    public function getImage($imageConfig = [])
    {
        $image = $this->createImage($imageConfig);

        if ($image->image && $this->isImage($image->imagesDir . $image->getIdRowPath() . $image->image)) {
            if (!$image->width && !$image->height) {
                $image->src = $image->imagesWebDir . $image->getIdRowPath() . $image->image;
            } else {
                $image->src = $this->resize($image);
            }
        } else {
            if (!$image->width && !$image->height) {
                $image->src = $image->defaultWebDir . $image->defaultImage;
            } else {
                $image->src = $this->resizeDefault($image);
            }
        }
        return $image;
    }

    /**
     * @param array $imageConfig
     * @return string
     * @throws \yii\base\Exception
     */
    public function image($imageConfig = [])
    {
        $image = $this->getImage($imageConfig);
        return $image->src;
    }

    /**
     * @param array $imageConfig
     * @return string
     */
    public function showImage($imageConfig = [])
    {

        $image = $this->getImage($imageConfig);

        if ($image->absolutePath === true) {
            $src = Yii::$app->getRequest()->getHostInfo() . $image->src;
        } else if(is_string($image->absolutePath)) {
            $src = $image->absolutePath . $image->src;
        } else {
            $src = $image->src;
        }


        return Html::img($src, $image->options);
    }

    /**
     * @param $image \pavlinter\display2\Image
     * @return string
     */
    public function resizeDefault($image)
    {
        $filePath   = $image->defaultDir . $image->defaultImage;


        if ($image->generalDefaultDir) {
            $defCat = $image->defaultCategory . '/';
        } else {
            $defCat = $image->category . '/' . $image->defaultCategory . '/';
        }
        $defaultDir      = Yii::getAlias(rtrim($this->cacheDir, '/')) . '/' . $defCat;
        $defaultWebDir   = Yii::getAlias(rtrim($this->cacheWebDir, '/')) . '/' . $defCat;
        FileHelper::createDirectory($defaultDir);


        $exists = file_exists($defaultDir . $image->sizeDirectory . $image->defaultImage);
        if ($exists && $this->cacheSeconds !== null) {
            $cacheFiletime = filemtime($defaultDir . $image->sizeDirectory . $image->defaultImage);
            if ($this->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                if ($filemtime !== $cacheFiletime) {
                    $exists = false;
                }
            } else {
                $exists = time() <= $this->cacheSeconds + $cacheFiletime;
            }
        }

        if (!$exists) {

            if ($this->_maxResized >= $this->maxResize) {
                return $defaultWebDir . $image->defaultImage;
            }
            $this->_maxResized++;

            FileHelper::createDirectory($defaultDir . $image->sizeDirectory);
            $img = \yii\imagine\Image::getImagine()->open($filePath);
            if ($image->resize instanceof \Closure) {
                $img = call_user_func($image->resize, $image, $img);
            } elseif ($image->mode === $image::MODE_STATIC) {
                $img = $this->resizeStatic($image, $img);
            } elseif ($image->mode === $image::MODE_MIN) {
                $img = $this->resizeMin($image, $img);
            } else {
                $img = $img->thumbnail(new Box($image->width, $image->height), $image->mode);
            }
            $newImage = $defaultDir . $image->sizeDirectory . $image->defaultImage;

            $img->save($newImage);
            if ($this->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                touch($newImage, $filemtime);
            }
        }
        return $defaultWebDir . $image->sizeDirectory . $image->defaultImage;
    }

    /**
     * @param $image \pavlinter\display2\Image
     * @return string
     */
    public function resize($image)
    {
        $filePath   = $image->imagesDir . $image->getIdRowPath() . $image->image;
        $img        = \yii\imagine\Image::getImagine()->open($filePath);
        $imageName  = $image->image;
        $basename   = basename($imageName);
        $dir        = '';
        if ($imageName != $basename) {
            $dir = dirname($imageName) . '/';
            $imageName = $basename;
            unset($basename);
        }

        if ($image->name) {
            $ext = '.' . DisplayHelper::getExtension($imageName);
            if ($image->encodeName instanceof \Closure) {
                $imageName = call_user_func($image->encodeName, $image->name). $ext;
            } else {
                $imageName = DisplayHelper::encodeName($image->name) . $ext;
            }
        }
        if (!isset($image->options['alt'])) {
            $image->options['alt'] = $imageName;
        }

        $imagesDir      = Yii::getAlias(rtrim($this->cacheDir, '/')) . '/' . $image->category . '/' . $image->getIdRowPath();
        $imagesWebDir   = Yii::getAlias(rtrim($this->cacheWebDir, '/')) . '/' . $image->category  . '/' . $image->getIdRowPath();
        FileHelper::createDirectory($imagesDir);

        $exists = file_exists($imagesDir . $image->sizeDirectory. $dir . $imageName);
        if ($exists && $this->cacheSeconds !== null) {
            $cacheFiletime = filemtime($imagesDir . $image->sizeDirectory. $dir . $imageName);
            if ($this->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                if ($filemtime !== $cacheFiletime) {
                    $exists = false;
                }
            } else {
                $exists = time() <= $this->cacheSeconds + $cacheFiletime;
            }
        }
        if (!$exists) {
            if ($this->_maxResized >= $this->maxResize) {
                return $image->imagesWebDir . $image->getIdRowPath() . $image->image;
            }
            $this->_maxResized++;

            if ($image->resize instanceof \Closure) {
                $img = call_user_func($image->resize, $image, $img);
            } elseif ($image->mode === $image::MODE_STATIC) {
                $img = $this->resizeStatic($image, $img);
            } elseif ($image->mode === $image::MODE_MIN) {
                $img = $this->resizeMin($image, $img);
            } else {
                $img = $img->thumbnail(new Box($image->width, $image->height), $image->mode);
            }
            FileHelper::createDirectory($imagesDir . $image->sizeDirectory . $dir);
            $newImage = $imagesDir . $image->sizeDirectory . $dir . $imageName;
            $img->save($newImage);

            if ($this->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                touch($newImage, $filemtime);
            }
        }
        return $imagesWebDir . $image->sizeDirectory . $dir . $imageName;
    }
    /**
     * @param $image \pavlinter\display2\Image
     * @param $originalImage
     * @return mixed
     */
    public function resizeStatic($image,$originalImage)
    {
        $Box = new Box($image->width, $image->height);
        $newImage = $originalImage->thumbnail($Box);
        $boxNew = $newImage->getSize();

        $x = ($Box->getWidth() - $boxNew->getWidth())/2;
        $y = ($Box->getHeight() - $boxNew->getHeight())/2;

        $point = new \Imagine\Image\Point($x,$y);
        $color = new \Imagine\Image\Color('#' . $image->bgColor, $image->bgAlpha);

        return \yii\imagine\Image::getImagine()->create($Box, $color)->paste($newImage, $point);
    }


    /**
     * @param $image \pavlinter\display2\Image
     * @param $originalImage
     * @return mixed
     */
    public function resizeMin($image,$originalImage)
    {
        /* @var $originalImage \Imagine\Imagick\Image */
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
        $color = new \Imagine\Image\Color('#' . $image->bgColor, $image->bgAlpha);

        return \yii\imagine\Image::getImagine()->create($Box, $color)->paste($newImage, $point);
    }



    /**
     * @param $path
     * @return array|bool
     */
    public function isImage($path)
    {
        if (!is_file($path)) {
            return false;
        }
        $ext = $this->getExtension($path);
        return $this->supported($ext);
    }

    /**
     * @param $path
     * @return string
     */
    public function getExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public function encodeName($string) {

        if (function_exists('iconv')) {
            $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
        $string = preg_replace("/[^a-zA-Z0-9 \-]/", "", $string);
        $string = str_replace("-",' ', $string);
        $string = trim(preg_replace("/\\s+/", " ", $string));
        $string = strtolower($string);
        $string = str_replace(" ", "-", $string);

        return $string;
    }

    /**
     * @param null $format
     * @return array|bool
     */
    public function supported($format = null)
    {
        $formats = ['gif', 'jpeg', 'jpg', 'png', 'wbmp', 'xbm'];

        if ($format === null) {
            return $formats;
        }
        $format  = strtolower($format);
        return in_array($format, $formats);
    }

}
