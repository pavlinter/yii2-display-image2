<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs <pavlinter@gmail.com>, 2015
 * @package yii2-adm-mailing
 */

namespace pavlinter\admmailing\components;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class Type
 */
class Type extends \yii\base\Component
{
    /**
     * Event after send all emails and before output response
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * Event before send one email
     */
    const EVENT_FILTER_DATA = 'filterData';
    /**
     * @var boolean
     */
    public $showAllstatistic = false;
    /**
     * @var boolean
     */
    public $disableDefaultLang = false;
    /**
     * @var boolean
     */
    public $testMode = false;
    /**
     * @var integer
     */
    public $sendSleep = 2;
    /**
     * @var integer
     */
    public $countIteration = 10;
    /**
     * @var string
     */
    public $label = "Not set";
    /**
     * @var string
     */
    public $emailKey = "email";
    /**
     * @var \Closure
     */
    private $_query;
    /**
     * @var \Closure
     */
    private $_var;
    /**
     * @var \Closure
     */
    private $_emailFilter;

    /**
     * @param $row
     * @return array
     */
    public function getVarTemplate($row)
    {
        $replace = call_user_func($this->getVar(), $row);
        if (!is_array($replace)) {
            throw new InvalidConfigException('The "var" property must be Closure and return array.');
        }
        return $replace;
    }

    /**
     * @inheritdoc
     * @return mixed
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @inheritdoc
     * @param $value
     * @throws InvalidConfigException
     */
    public function setQuery($value)
    {
        if (!($value instanceof \Closure)) {
            throw new InvalidConfigException('The "query" property must be Closure.');
        }
        $this->_query = $value;
    }

    /**
     * @inheritdoc
     * @return \Closure
     */
    public function getVar()
    {
        if ($this->_var === null) {
            $this->setVar(function ($row) {
                $replace = [];
                foreach ($row as $name => $value) {
                    $replace['{' . $name . '}'] = $value;
                }
                return $replace;
            });
        }
        return $this->_var;
    }

    /**
     * @inheritdoc
     * @param $value
     * @throws InvalidConfigException
     */
    public function setVar($value)
    {
        if (!($value instanceof \Closure)) {
            throw new InvalidConfigException('The "var" property must be Closure.');
        }
        $this->_var = $value;
    }

    /**
     * @inheritdoc
     * @return \Closure
     */
    public function getEmailFilter()
    {
        if ($this->_emailFilter === null) {
            $this->setEmailFilter(function ($email, $row) {
                $model = \yii\base\DynamicModel::validateData(['email' => $email], [
                    ['email', 'email'],
                ]);
                return !$model->hasErrors();
            });
        }
        return $this->_emailFilter;
    }
    /**
     * @inheritdoc
     * @param $value
     * @throws InvalidConfigException
     */
    public function setEmailFilter($value)
    {
        if (!($value instanceof \Closure)) {
            throw new InvalidConfigException('The "emailFilter" property must be Closure.');
        }
        $this->_emailFilter = $value;
    }
}