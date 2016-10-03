<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.0.0
 */

namespace pavlinter\display2\components;

use pavlinter\display2\DisplayAsset;
use pavlinter\display2\objects\Image;
use pavlinter\display2\objects\ResizeModeInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * //config
 *  'modules' => [
 *      'display2'=> [
 *          'class'=>'pavlinter\display2\Module',
 *          'categories' => [
 *              'all' => [
 *                  'imagesWebDir' => '@web/display-images/images',
 *                  'imagesDir' => '@webroot/display-images/images',
 *                  'defaultWebDir' => '@web/display-images/default',
 *                  'defaultDir' => '@webroot/display-images/default',
 *                  'mode' => \pavlinter\display2\objects\Image::MODE_OUTBOUND,
 *              ],
 *              'items' => [
 *                  'generalDefaultDir' => false,
 *                  'imagesWebDir' => '@web/display-images/items',
 *                  'imagesDir' => '@webroot/display-images/items',
 *                  'defaultWebDir' => '@web/display-images/default/items',
 *                  'defaultDir' => '@webroot/display-images/default/items',
 *                  'mode' => \pavlinter\display2\objects\Image::MODE_STATIC,
 *              ],
 *          ],
 *     ],
 *  ],
 *  'components' => [
 *      'display' => [
 *          'class' => 'pavlinter\display2\components\Display',
 *          'resizeModes' => [
 *             'ownResizeMode' => 'pavlinter\display2\objects\ResizeMode',
 *             'ownResizeModeParams' => [
 *                  'class' => 'pavlinter\display2\objects\ResizeMode',
 *              ],
 *             'ownResizeModeFunc' => function ($image, $originalImage) {
 *                  @var $this \pavlinter\display2\components\Display
 *                  @var $image \pavlinter\display2\objects\Image
 *                  @var $originalImage \Imagine\Gd\Image
 *                  return $originalImage->thumbnail(new \Imagine\Image\Box($image->width, $image->height), \pavlinter\display2\objects\Image::MODE_OUTBOUND);
 *             }
 *          ],
 *      ],
 *  ],
 *
 *  $images =  Yii::$app->display->getFileImgs(1, 'items', [
 *      'width' => 100,
 *      'height' => 100,
 *      'mode' => \pavlinter\display2\objects\Image::MODE_OUTBOUND,
 *      'loadingOptions' => [],
 *  ],[
 *      'dir' => 'mainDir',
 *      'minImages' => 2,
 *      'maxImages' => 6,
 *      'recursive' => false,
 *  ]);
 *   //return
 *  [
 *      1204270244_1.jpg' => [
 *          'id_row' => 1
 *          'key' => 0
 *          'fullPath' => 'basePath..../web/display-images/items/1/1204270244_1.jpg'
 *          'dirName' => '1204270244_1.jpg'
 *          'imagesDir' => 'basePath..../web/display-images/items/1/'
 *          'imagesWebDir' => '/display-images/items/1/'
 *          'originImage' => '/display-images/items/1/1204270244_1.jpg'
 *          'image' => '1204270244_1.jpg'
 *          'display' => '/ru/display2/image/crop?width=100&height=100&mode=outbound&category=items&id_row=1&image=1204270244_1.jpg'
 *          'displayLoading' => '<div class=\"display\" style=\"width: 100px; height: 100px;\"><img src=\"/ru/display2/image/crop?width=100&amp;height=100&amp;mode=outbound&amp;category=items&amp;id_row=1&amp;image=1204270244_1.jpg\" alt=\"1204270244_1\"><div class=\"display-loading\"></div></div>'
 *      ]
 *  ]
 *
 *  $image =  Yii::$app->display->getFileImg(1, 'items', [
 *      'width' => 100,
 *      'height' => 100,
 *      'mode' => \pavlinter\display2\objects\Image::MODE_OUTBOUND,
 *      'loadingOptions' => [],
 *  ]);
 *  //return
 *  [
 *      'id_row' => 1
 *      'key' => 0
 *      'fullPath' => 'basePath..../web/display-images/items/1/1204270244_1.jpg'
 *      'dirName' => '1204270244_1.jpg'
 *      'imagesDir' => 'basePath..../web/display-images/items/1/'
 *      'imagesWebDir' => '/display-images/items/1/'
 *      'originImage' => '/display-images/items/1/1204270244_1.jpg'
 *      'image' => '1204270244_1.jpg'
 *      'display' => '/ru/display2/image/crop?width=100&height=100&mode=outbound&category=items&id_row=1&image=1204270244_1.jpg'
 *      'displayLoading' => '<div class=\"display\" style=\"width: 100px; height: 100px;\"><img src=\"/ru/display2/image/crop?width=100&amp;height=100&amp;mode=outbound&amp;category=items&amp;id_row=1&amp;image=1204270244_1.jpg\" alt=\"1204270244_1\"><div class=\"display-loading\"></div></div>'
 *  ]
 *
 *  $files =  Yii::$app->display->getRowFiles(1, 'items', [
 *      'dir' => 'gallery',
 *  ]);
 *  //return
 *  [
 *      1204270244_1.jpg' => [
 *          'id_row' => 1
 *          'key' => 0
 *          'fullPath' => 'basePath..../web/display-images/items/1/\\1.jpg'
 *          'dirName' => '1.jpg'
 *          'imagesDir' => 'basePath..../web/display-images/items/'
 *          'imagesWebDir' => '/display-images/items/'
 *          'originImage' => '/display-images/items/1/1.jpg'
 *          'image' => '1.jpg'
 *      ]
 *  ]
 *
 *  $file = Yii::$app->display->getRowFile(1, 'items', [
 *      'dir' => 'main',
 *  ]);
 *  //return
 *  [
 *      'id_row' => 1
 *      'key' => 0
 *      'fullPath' => 'basePath..../web/display-images/items/1/\\1.jpg'
 *      'dirName' => '1.jpg'
 *      'imagesDir' => 'basePath..../web/display-images/items/'
 *      'imagesWebDir' => '/display-images/items/'
 *      'originImage' => '/display-images/items/1/1.jpg'
 *      'image' => '1.jpg'
 *  ]
 *
 *  echo Yii::$app->display->showCropImage([ //return default Html::img from items category
 *      'id_row' => 2,
 *      'width' => 100,
 *      'name' => 'newName',
 *      'image' => '1.jpeg',
 *      'category' => 'items',
 *  ]);
 *
 *  echo Yii::$app->display->showCropImage([ //return original Html::img
 *      'image' => '1.jpeg',
 *      'category' => 'all',
 *  ]);
 *
 *
 *  echo Yii::$app->display->createUrl([
 *      'width' => 120,
 *      'image' => '/subfolders/bg.jpg',
 *      'category' => 'all',
 *      'mode' => \pavlinter\display2\objects\Image::MODE_OUTBOUND,
 *  ]);
 *  echo Yii::$app->display->showImg([
 *      'id_row' => 2,
 *      'width' => 100,
 *      'image' => 'd.jpeg',
 *      'category' => 'items',
 *      'mode' => \pavlinter\display2\objects\Image::MODE_STATIC,
 *  ]);
 *
 * Class Display
 * @property \pavlinter\display2\Module $displayModule
 */
class Display extends \yii\base\Component
{

    public $moduleId = 'display2';

    public $view = 'view';

    public $loadingOptions = [];

    public $loadingBgSize = [
        'width' => 64,
        'height' => 64,
    ];

    public $resizeModes = [];

    private $_defaultResizeModes = [
        Image::MODE_OUTBOUND => 'pavlinter\display2\objects\ResizeMode',
        Image::MODE_INSET => 'pavlinter\display2\objects\ResizeMode',
        Image::MODE_STATIC => 'pavlinter\display2\objects\ResizeModeStatic',
        Image::MODE_MIN => 'pavlinter\display2\objects\ResizeModeMin',
    ];

    private $_displayModule;

    /**
     * @var integer already resized
     */
    private $_maxResized = 0;

    /**
     *
     */
    public function init()
    {
        $this->resizeModes = ArrayHelper::merge($this->_defaultResizeModes, $this->resizeModes);
        parent::init();
    }

    /**
     * @param array $params
     * @return string
     */
    public function createUrl($params = [])
    {
        $url = $this->urlTo(['//' . $this->moduleId . '/image/crop']);
        if (strpos($url, '?') === false) {
            $url .= '?';
        }
        $url .= http_build_query($params);
        return $url;
    }

    /**
     * @param $config
     * @param array $options
     * @return string
     */
    public function showImg($config, $options = [])
    {
        return $this->htmlImg($this->createUrl($config), $options);
    }

    /**
     * @param $src
     * @param array $options
     * @return string
     */
    public function htmlImg($src, $options = [])
    {
        return Html::img($src, $options);
    }

    /**
     * @param $src
     * @param array $imgOptions
     * @param array $loadingOptions
     * @return string
     */
    public function loadingBoxImg($src, $imgOptions = [], $loadingOptions = [])
    {
        return $this->loadingBox($this->htmlImg($src, $imgOptions), $loadingOptions);
    }

    /**
     * @param $img
     * @param array $loadingOptions
     * @return string
     */
    public function loadingBox($img, $loadingOptions = [])
    {
        DisplayAsset::register($this->getView());
        $loadingOptions = ArrayHelper::merge($this->loadingOptions, $loadingOptions);
        Html::addCssClass($loadingOptions, 'display');

        $width = ArrayHelper::remove($loadingOptions, 'width', '100%');
        $height = ArrayHelper::remove($loadingOptions, 'height', 400);

        if (is_integer($width) && $width < $this->loadingBgSize['width']) {
            Html::addCssClass($loadingOptions, 'bg-size');
        } elseif (is_integer($height) && $height < $this->loadingBgSize['height']) {
            Html::addCssClass($loadingOptions, 'bg-size');
        }

        if (is_integer($width)) {
            $width .= 'px';
        }
        if (is_integer($height)) {
            $height .= 'px';
        }
        Html::addCssStyle($loadingOptions, ['width' => $width, 'height' => $height]);
        if ($width == '100%') {
            Html::addCssStyle($loadingOptions, ['display' => 'block']);
        }
        $html  = Html::beginTag('div', $loadingOptions);
        $html .= $img;
        $html .= Html::tag('div', null ,['class' => 'display-loading']);
        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $config
     * @param array $options
     * @return array
     */
    public function getFileImgs($id_row, $category, $config = [], $options = [])
    {
        $options['isDisplayImagePath'] = true;
        $images     = $this->getOriginalImages($id_row, $category, $options);

        $displayImages = [];
        $config['category'] = $category;

        $loadingOptions = ArrayHelper::remove($config, 'loadingOptions', []);
        $imgOptions = ArrayHelper::remove($config, 'imgOptions', []);

        if ($id_row) {
            $config['id_row'] = $id_row;
        }
        foreach ($images as $k => $image) {
            if (is_array($image)) {
                $image['alt'] = null;
                $config['image'] = $image['dirName'];

                if (!isset($config['v'])) {
                    $timestamp = @filemtime($image['fullPath']);
                    if ($timestamp > 0) {
                        $config['v'] = $timestamp;
                    }
                }
                $image['display'] = $this->createUrl($config);
                if (!isset($imgOptions['alt'])) {
                    $image['alt'] = $imgOptions['alt'] = pathinfo($image['fullPath'], PATHINFO_FILENAME);
                }

                if ($loadingOptions === false) {
                    $image['displayLoading'] = null;
                } else {
                    if (!isset($loadingOptions['height']) && isset($config['height'])) {
                        $loadingOptions['height'] = $config['height'];
                    }
                    if (!isset($loadingOptions['width']) && isset($config['width'])) {
                        $loadingOptions['width'] = $config['width'];
                    }
                    $image['displayLoading'] = $this->loadingBoxImg($image['display'], $imgOptions, $loadingOptions);

                }

                if ($image['image'] === null) {
                    $image['image'] = $image['display'];
                }
                if ($image['originImage'] === null) {
                    $image['originImage'] = $image['display'];
                }

                $displayImages[$k] = $image;
            } else {
                $config['image'] = $image;
                $displayImages[$k] = $this->createUrl($config);
            }
        }
        return $displayImages;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $config
     * @param array $options
     * @return mixed|null
     */
    public function getFileImg($id_row, $category, $config = [], $options = [])
    {
        $options['maxImages'] = 1;
        if (!isset($options['minImages'])) {
            $options['minImages'] = 1;
        }
        $displayImages = $this->getFileImgs($id_row, $category, $config, $options);
        if ($displayImages) {
            return reset($displayImages);
        }
        return null;
    }

    /**
     * @param string $url
     * @param bool|false $scheme
     * @return string
     */
    public function urlTo($url = '', $scheme = false)
    {
        return Url::to($url, $scheme);
    }

    /**
     * @param array $imageConfig
     * @return string
     */
    public function showCropImage($imageConfig = [])
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
        $imageConfig = $this->displayModule->imageConfig;
        if ($imageConfig instanceof \Closure) {
            $imageConfig = call_user_func($imageConfig, $config);
        }
        if (!isset($imageConfig['class'])) {
            $imageConfig['class'] = $this->displayModule->imageClass;
        }

        $config = ArrayHelper::merge($imageConfig, $config);

        if (!isset($config['category'])) {
            throw new InvalidConfigException('The "category" property must be set.');
        }
        if (!isset($this->displayModule->categories[$config['category']])) {
            throw new InvalidConfigException('Set category in Module configuration!');
        }
        return Yii::createObject(ArrayHelper::merge($this->displayModule->categories[$config['category']], $config));
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

        $imagesDir      = Yii::getAlias(rtrim($this->displayModule->cacheDir, '/')) . '/' . $image->category . '/' . $image->getIdRowPath();
        $imagesWebDir   = Yii::getAlias(rtrim($this->displayModule->cacheWebDir, '/')) . '/' . $image->category  . '/' . $image->getIdRowPath();
        FileHelper::createDirectory($imagesDir);

        $exists = file_exists($imagesDir . $image->sizeDirectory. $dir . $imageName);
        if ($exists && $this->displayModule->cacheSeconds !== null) {
            $cacheFiletime = filemtime($imagesDir . $image->sizeDirectory. $dir . $imageName);
            if ($this->displayModule->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                if ($filemtime !== $cacheFiletime) {
                    $exists = false;
                }
            } else {
                $exists = time() <= $this->displayModule->cacheSeconds + $cacheFiletime;
            }
        }
        if (!$exists) {
            if ($this->_maxResized >= $this->displayModule->maxResize) {
                $image->src = $image->imagesWebDir . $image->getIdRowPath() . $image->image;
                $image->rootSrc = $image->imagesDir . $image->getIdRowPath() . $image->image;
                return false;
            }
            $this->_maxResized++;

            $img = $this->callResizeMode($image, $img);
            FileHelper::createDirectory($imagesDir . $image->sizeDirectory . $dir);
            $newImage = $imagesDir . $image->sizeDirectory . $dir . $imageName;
            $img->save($newImage);

            if ($this->displayModule->cacheSeconds === 'auto') {
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
        $defaultDir      = Yii::getAlias(rtrim($this->displayModule->cacheDir, '/')) . '/' . $defCat;
        $defaultWebDir   = Yii::getAlias(rtrim($this->displayModule->cacheWebDir, '/')) . '/' . $defCat;
        FileHelper::createDirectory($defaultDir);

        if (!isset($image->options['alt'])) {
            $image->options['alt'] = basename($image->defaultImage);
        }

        $exists = file_exists($defaultDir . $image->sizeDirectory . $image->defaultImage);
        if ($exists && $this->displayModule->cacheSeconds !== null) {
            $cacheFiletime = filemtime($defaultDir . $image->sizeDirectory . $image->defaultImage);
            if ($this->displayModule->cacheSeconds === 'auto') {
                $filemtime = filemtime($filePath);
                if ($filemtime !== $cacheFiletime) {
                    $exists = false;
                }
            } else {
                $exists = time() <= $this->displayModule->cacheSeconds + $cacheFiletime;
            }
        }

        if (!$exists) {

            if ($this->_maxResized >= $this->displayModule->maxResize) {
                $image->src = $defaultWebDir . $image->defaultImage;
                $image->rootSrc = $defaultDir . $image->defaultImage;
                return false;
            }
            $this->_maxResized++;

            FileHelper::createDirectory($defaultDir . $image->sizeDirectory);
            $img = \yii\imagine\Image::getImagine()->open($filePath);
            $img = $this->callResizeMode($image, $img);

            $newImage = $defaultDir . $image->sizeDirectory . $image->defaultImage;

            $img->save($newImage);
            if ($this->displayModule->cacheSeconds === 'auto') {
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
     * @param $originalImage \Imagine\Gd\Image
     * @return mixed
     * @throws InvalidConfigException
     */
    public function callResizeMode($image, $originalImage)
    {
        if (isset($this->resizeModes[$image->mode])) {
            $mode = $this->resizeModes[$image->mode];

            if($mode instanceof \Closure) {
                return call_user_func($mode, $image, $originalImage);
            }else if(is_string($mode) || is_array($mode)){
                $resizeMode = Yii::createObject($mode);
                if (!$resizeMode instanceof ResizeModeInterface) {
                    throw new InvalidConfigException('ResizeMode class must implement ResizeModeInterface.');
                }
                return $resizeMode->resize($image, $originalImage);
            } else {
                throw new InvalidConfigException('The "' . $image->mode . '" property must be Closure or ResizeModeInterface');
            }
        } else {
            throw new InvalidConfigException('The "' . $image->mode . '" mode not exist.');
        }
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
     * @param $id_row
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public function getRowFile($id_row, $category, $options = [])
    {
        $options['maxImages'] = 1;
        $files = $this->getRowFiles($id_row, $category, $options);
        if ($files) {
            return reset($files);
        }
        return null;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public function getRowFiles($id_row, $category, $options = [])
    {
        $options['id_row'] = $id_row;
        return $this->getFiles($category, $options);
    }

    /**
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public function getFiles($category, $options = [])
    {
        if (!isset($this->displayModule->categories[$category])) {
            return false;
        }
        $config = $this->displayModule->categories[$category];
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
            'return' => function($data){
                return $data;
            }, //required return string|array and image key
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
                $pathName = trim(str_replace($imagesDir . $id_row, '', $image), '\\');
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
                    $resImages[$key] = basename($pathName);
                } else {
                    $data['image'] = basename($pathName);
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
                    $resImages[$key] = basename($data['originImage']);
                } else {
                    $data['image'] = basename($data['originImage']);
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
     * @return array
     */
    public function getOriginalImages($id_row, $category, $options = [])
    {
        if ($id_row) {
            $options['id_row'] = $id_row;
        }
        if (isset($options['dir'])) {
            $options['dir'] = trim($options['dir'], '/');
        }
        if (!isset($options['only'])) {
            $extensions = $this->supported();
            foreach ($extensions as $ext) {
                $options['only'][] = '*.' . $ext;
            }
            if ($options['only']) {
                $options['caseSensitive'] = false;
            }
        }
        $files = $this->getFiles($category, $options);
        if (!is_array($files)) {
            return [];
        }
        return $files;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return array
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
    public function getCropFileImages($id_row, $category, $widget = [], $options = [])
    {
        $options['isDisplayImagePath'] = true;
        $images     = $this->getOriginalImages($id_row, $category, $options);
        $displayImages = [];
        $widget['category'] = $category;
        if ($id_row) {
            $widget['id_row'] = $id_row;
        }

        $loadingOptions = ArrayHelper::remove($options, 'loadingOptions', []);
        $imgOptions = ArrayHelper::remove($options, 'imgOptions', []);

        foreach ($images as $k => $image) {

            if (is_array($image)) {
                $image['alt'] = null;
                $widget['image'] = $image['dirName'];
                $imageObject = $this->getImage($widget);
                if ($imageObject->name !== null) {
                    $image['alt'] = $imgOptions['alt'] = $imageObject->name;
                }
                $image['display'] = $imageObject->src;

                if (!isset($imgOptions['alt']) && $imageObject->name === null) {
                    $image['alt'] = $imgOptions['alt'] = pathinfo($image['fullPath'], PATHINFO_FILENAME);
                }

                if ($loadingOptions === false) {
                    $image['displayLoading'] = null;
                } else {
                    if (!isset($loadingOptions['height']) && isset($options['height'])) {
                        $loadingOptions['height'] = $options['height'];
                    }
                    if (!isset($loadingOptions['width']) && isset($options['width'])) {
                        $loadingOptions['width'] = $options['width'];
                    }
                    $image['displayLoading'] = $this->loadingBoxImg($image['display'], $imgOptions, $loadingOptions);
                }

                if ($image['image'] === null) {
                    $image['image'] = $image['display'];
                }
                if ($image['originImage'] === null) {
                    $image['originImage'] = $image['display'];
                }
                $displayImages[$k] = $image;
            } else {
                $widget['image'] = $image;
                $displayImages[$k] = $this->getImage($widget)->src;
            }
        }
        return $displayImages;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $widget
     * @param array $options
     * @return array
     */
    public function getCropFileImage($id_row, $category, $widget = [], $options = [])
    {
        $options['maxImages'] = 1;
        $images = $this->getCropFileImages($id_row, $category, $widget, $options);
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
    /*public function clear($category, $id_row = null)
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
    }*/

    /**
     * Clear all cache (Only for outer directory cache)
     */
    /*public function clearCacheDir()
    {
        $globalConfig = static::getConfig();

        $innerCacheDir = ArrayHelper::remove($globalConfig, 'innerCacheDir');
        if (!$innerCacheDir) {
            $cacheDir = Yii::getAlias(rtrim(ArrayHelper::remove($globalConfig, 'cacheDir', static::CACHE_DIR), '/'));
            FileHelper::removeDirectory($cacheDir);
            return true;
        }
        return false;
    }*/

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

    /**
     * @return \yii\web\View
     */
    public function getView()
    {
        return Yii::$app->get($this->view);
    }

}
