<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs <pavlinter@gmail.com>, 2015
 * @package yii2-adm-mailing
 */

namespace pavlinter\admmailing\components;

use Yii;

/**
 * Class TypeEvent
 */
class TypeEvent extends \yii\base\Event
{
    public $row = [];

    public $json = [];

    public $model;

    public $module;

    public $isValid = true;
}