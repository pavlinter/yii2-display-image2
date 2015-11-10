<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.0.0
 */

namespace pavlinter\display2\components;

use Imagine\Image\Box;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;

/**
 * Class Display
 * @property \pavlinter\display2\Module $displayModule
 */
class Display extends \yii\base\Component
{

    public $moduleId = 'display2';

    public $_displayModule;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
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
     * @param array $imageConfig
     * @return \pavlinter\display2\objects\Image
     */
    public function getImage($imageConfig = [])
    {
        $image = $this->createImage($imageConfig);

        if ($image->image && $this->isImage($image->imagesDir . $image->getIdRowPath() . $image->image)) {
            if (!$image->width && !$image->height) {
                $image->src = $image->imagesWebDir . $image->getIdRowPath() . $image->image;
                $image->rootSrc = $image->imagesDir . $image->getIdRowPath() . $image->image;
            } else {
                $this->resize($image);
            }
        } else {
            if (!$image->width && !$image->height) {
                $image->src = $image->defaultWebDir . $image->defaultImage;
                $image->rootSrc = $image->defaultDir . $image->defaultImage;
            } else {
                $this->resizeDefault($image);
            }
        }
        $image->appendTimestamp();
        return $image;
    }

    /**
     * @param array $config
     * @return \pavlinter\display2\objects\Image
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

        $config = ArrayHelper::merge($imageConfig, $config);

        if (!isset($config['category'])) {
            throw new InvalidConfigException('The "category" property must be set.');
        }
        if (!isset($this->categories[$config['category']])) {
            throw new InvalidConfigException('Set category in Display configuration!');
        }

        return Yii::createObject(ArrayHelper::merge($this->categories[$config['category']], $config));
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
     * @param $image \pavlinter\display2\objects\Image
     * @return bool
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
            $ext = '.' . $this->getExtension($imageName);
            if ($image->encodeName instanceof \Closure) {
                $imageName = call_user_func($image->encodeName, $image->name). $ext;
            } else {
                $imageName = $this->encodeName($image->name) . $ext;
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
                $image->src = $image->imagesWebDir . $image->getIdRowPath() . $image->image;
                $image->rootSrc = $image->imagesDir . $image->getIdRowPath() . $image->image;
                return false;
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

        $image->src = $imagesWebDir . $image->sizeDirectory . $dir . $imageName;
        $image->rootSrc = $imagesDir . $image->sizeDirectory . $dir . $imageName;
        return true;
    }

    /**
     * @param $image \pavlinter\display2\objects\Image
     * @return bool
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

        if (!isset($image->options['alt'])) {
            $image->options['alt'] = basename($image->defaultImage);
        }

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
                $image->src = $defaultWebDir . $image->defaultImage;
                $image->rootSrc = $defaultDir . $image->defaultImage;
                return false;
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
        $image->src = $defaultWebDir . $image->sizeDirectory . $image->defaultImage;
        $image->rootSrc = $defaultDir . $image->sizeDirectory . $image->defaultImage;
        return true;
    }

    /**
     * @param $image \pavlinter\display2\objects\Image
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
     * @param $image \pavlinter\display2\objects\Image
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













    /**
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public function getFiles($category, $options = [])
    {
        if (!isset($this->categories[$category])) {
            return false;
        }
        $config = $this->categories[$category];
        if (!isset($config['imagesDir'])) {
            throw new InvalidConfigException('The "imagesDir" property must be set for "' . $category . '".');
        }
        $imagesDir      = Yii::getAlias($config['imagesDir']) . '/';
        $imagesWebDir   = Yii::getAlias($config['imagesWebDir']) . '/';

        $options = ArrayHelper::merge([
            'recursive' => false,
            'dir' => '',
            'isDisplayImagePath' => false,
            'id_row' => null,
            'defaultImage' => null,
            'keyCallback' => function($data){
                return basename($data['dirName']);
            },
            'return' => false, // or function($data){ return $data; } required return string|array and image key
            'minImages' => 0
        ], $options);



        if ($options['return'] !== false && !($options['return'] instanceof \Closure)) {
            throw new InvalidConfigException('The "return" property must be Closure.');
        }


        $dir = ArrayHelper::remove($options, 'dir');
        $dir = $dir ? $dir . '/' : '';
        $keyCallback = ArrayHelper::remove($options, 'keyCallback');
        $isDisplayImagePath = ArrayHelper::remove($options, 'isDisplayImagePath');
        $defaultImage = ArrayHelper::remove($options, 'defaultImage');
        $minImages = ArrayHelper::remove($options, 'minImages');
        $maxImages = ArrayHelper::remove($options, 'maxImages');
        $id_row = ArrayHelper::remove($options, 'id_row');
        $id_row = $id_row ? $id_row . '/' : '';

        $toDir = $imagesDir . $id_row . $dir;
        FileHelper::createDirectory($toDir);
        $images = FileHelper::findFiles($toDir, $options);
        $resImages = [];
        if ($isDisplayImagePath) {
            foreach ($images as $k => $image) {
                if ($maxImages !== null && $k >= $maxImages) {
                    break;
                }
                $pathName = str_replace($imagesDir . $id_row, '', str_replace('\\', '', $image));
                $data = [
                    'id_row' => (int)$id_row,
                    'key' => $k,
                    'fullPath' => $image,
                    'dirName' => $pathName,
                    'imagesDir' => $imagesDir . $id_row,
                    'imagesWebDir' => $imagesWebDir . $id_row,
                    'originImage' => $imagesWebDir . $id_row . $pathName,
                ];
                $key = call_user_func($keyCallback, $data);
                if ($options['return'] === false) {
                    $resImages[$key] = $pathName;
                } else {
                    $data['image'] = $pathName;
                    $resImages[$key] = call_user_func($options['return'], $data);
                }
            }
        } else {
            foreach ($images as $k => $image) {
                if ($maxImages !== null && $k >= $maxImages) {
                    break;
                }
                $pathName = str_replace($imagesDir . $id_row, '', str_replace('\\', '', $image));
                $data = [
                    'id_row' => (int)$id_row,
                    'key' => $k,
                    'fullPath' => $image,
                    'dirName' => $pathName,
                    'imagesDir' => $imagesDir,
                    'imagesWebDir' => $imagesWebDir,
                    'originImage' => $imagesWebDir . $id_row . $pathName,
                ];
                $key = call_user_func($keyCallback, $data);
                if ($options['return'] === false) {
                    $resImages[$key] = $data['originImage'];
                } else {
                    $data['image'] = $data['originImage'];
                    $resImages[$key] = call_user_func($options['return'], $data);
                }
            }
        }


        if ($minImages && ($count = $minImages - count($resImages)) > 0) {

            if ($options['return'] === false) {
                for ($i = 0; $i < $count; $i++) {
                    $resImages[$i] = $defaultImage;
                }
            } else {

                for ($i = 0; $i < $count; $i++) {
                    $data = [
                        'id_row' => (int)$id_row,
                        'key' => $i,
                        'fullPath' => null,
                        'dirName' => null,
                        'imagesDir' => $imagesDir,
                        'imagesWebDir' => $imagesWebDir,
                        'originImage' => $defaultImage,
                        'image' => $defaultImage,
                    ];
                    $resImages[$i] = call_user_func($options['return'], $data);
                }
            }
        }
        return $resImages;
    }
    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public static function getOriginalImages($id_row, $category, $options = [])
    {
        if ($id_row) {
            $options['id_row'] = $id_row;
        }
        if (isset($options['dir'])) {
            $options['dir'] = trim($options['dir'], '/');
        }
        if (!isset($options['only'])) {
            $extensions = static::supported();
            foreach ($extensions as $ext) {
                $options['only'][] = '*.' . $ext;
            }
            if ($options['only']) {
                $options['caseSensitive'] = false;
            }
        }

        $files = static::getFiles($category, $options);

        if (!is_array($files)) {
            return [];
        }
        return $files;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return mixed|null
     */
    public static function getOriginalImage($id_row, $category, $options = [])
    {
        $images = static::getOriginalImages($id_row, $category, $options);
        if (empty($images)) {
            return null;
        }
        return reset($images);
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $widget
     * @param array $options
     * @return array
     */
    public static function getFileImages($id_row, $category, $widget = [], $options = [])
    {
        $options['isDisplayImagePath'] = true;
        $images     = static::getOriginalImages($id_row, $category, $options);
        $displayImages = [];
        if (!isset($widget['returnSrc'])) {
            $widget['returnSrc'] = true;
        }
        $widget['category'] = $category;
        if ($id_row) {
            $widget['id_row'] = $id_row;
        }
        foreach ($images as $k => $image) {
            if (is_array($image)) {
                $widget['image'] = $image['image'];
                $image['display'] = DisplayImage::widget($widget);
                if ($image['image'] === null) {
                    $image['image'] = $image['display'];
                }
                if ($image['originImage'] === null) {
                    $image['originImage'] = $image['display'];
                }

                $displayImages[$k] = $image;
            } else {
                $widget['image'] = $image;
                $displayImages[$k] = DisplayImage::widget($widget);
            }
        }
        return $displayImages;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $widget
     * @param array $options
     * @return mixed
     */
    public function getFileImage($id_row, $category, $widget = [], $options = [])
    {
        $images = static::getImages($id_row, $category, $widget, $options);

        if (empty($images)) {
            return null;
        }
        return reset($images);
    }

    /**
     * @param $category
     * @param null $id_row
     * @return bool
     */
    public function clear($category, $id_row = null)
    {
        $globalConfig = static::getConfig();
        $categories = ArrayHelper::remove($globalConfig, 'config');

        if (!isset($categories[$category])) {
            return false;
        }
        $innerCacheDir = ArrayHelper::remove($globalConfig, 'innerCacheDir');
        $cacheDir = Yii::getAlias(rtrim(ArrayHelper::remove($globalConfig, 'cacheDir', static::CACHE_DIR), '/'));
        $generalDefaultDir = ArrayHelper::remove($categories[$category], 'generalDefaultDir');
        if ($generalDefaultDir === null) {
            $generalDefaultDir = ArrayHelper::remove($globalConfig, 'generalDefaultDir', true);
        }
        $defaultCategory = ArrayHelper::remove($categories[$category], 'defaultCategory', static::DEFAULT_CATEGORY);
        $imagesDir = Yii::getAlias(rtrim(ArrayHelper::remove($categories[$category], 'imagesDir'), '/'));
        $defaultDir = Yii::getAlias(rtrim(ArrayHelper::remove($categories[$category], 'defaultDir'), '/'));

        if ($id_row) {
            $id_row = '/' . $id_row;
        }
        if ($innerCacheDir) {
            if (empty($imagesDir)) {
                return false;
            }
            $path = $imagesDir . $id_row . '/' . $innerCacheDir;
            $defaultCacheDir = $defaultDir . '/' . $innerCacheDir. '/';
        } else {
            $path = $cacheDir . '/' . $category . $id_row;
            if ($generalDefaultDir) {
                $defaultCacheDir = $cacheDir . '/' .$defaultCategory . '/';
            } else {
                $defaultCacheDir = $cacheDir . '/' .$category . '/' . $defaultCategory . '/';
            }
        }
        FileHelper::removeDirectory($path);
        FileHelper::removeDirectory($defaultCacheDir);
        return true;
    }

    /**
     * Clear all cache (Only for outer directory cache)
     */
    public function clearCacheDir()
    {
        $globalConfig = static::getConfig();

        $innerCacheDir = ArrayHelper::remove($globalConfig, 'innerCacheDir');
        if (!$innerCacheDir) {
            $cacheDir = Yii::getAlias(rtrim(ArrayHelper::remove($globalConfig, 'cacheDir', static::CACHE_DIR), '/'));
            FileHelper::removeDirectory($cacheDir);
            return true;
        }
        return false;
    }

    /**
     * @return \pavlinter\display2\Module
     */
    public function getDisplayModule()
    {
        if ($this->_displayModule === null) {
            $this->_displayModule = Yii::$app->getModule($this->moduleId);
        }
        return $this->_displayModule;
    }

}
