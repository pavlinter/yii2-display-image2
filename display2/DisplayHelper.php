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

    /**
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public static function getFiles($category, $options = [])
    {
        $globalConfig = static::getConfig();
        if (empty($globalConfig['config'])) {
            return false;
        }
        $categories = $globalConfig['config'];

        if (!isset($categories[$category])) {
            return false;
        }
        $config = $categories[$category];
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
    public static function getImages($id_row, $category, $widget = [], $options = [])
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
    public static function getImage($id_row, $category, $widget = [], $options = [])
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
    public static function clear($category, $id_row = null)
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
    public static function clearCacheDir()
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
     * @param $path
     * @return array|bool
     */
    public static function is_image($path)
    {
        if (!is_file($path)) {
            return false;
        }
        $ext = static::getExtension($path);
        return static::supported($ext);
    }

    /**
     * @param $path
     * @return string
     */
    public static function getExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public static function encodeName($string) {

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
    public static function supported($format = null)
    {
        $formats = ['gif', 'jpeg', 'jpg', 'png', 'wbmp', 'xbm'];

        if ($format === null) {
            return $formats;
        }
        $format  = strtolower($format);
        return in_array($format, $formats);
    }
}
