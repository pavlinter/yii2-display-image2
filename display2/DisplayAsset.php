<?php
/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 0.2.0
 */

namespace pavlinter\display2;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DisplayAsset extends AssetBundle
{
    public $sourcePath =  '@vendor/pavlinter/yii2-display-image2/display2/assets';
    public $css = [
        'css/style.css',
    ];
    public $js = [
        'js/display.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
