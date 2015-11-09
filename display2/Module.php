<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2015
 * @package yii2-display-image2
 * @version 2.0.0
 */

namespace pavlinter\admmailing;

use Closure;
use pavlinter\adm\Adm;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 *
 */
class Module extends \yii\base\Module
{
    /**
     * @var string
     */
    public $controllerNamespace = 'pavlinter\admmailing\controllers';
    /**
     * @var string
     */
    public $layout = '@vendor/pavlinter/yii2-adm/adm/views/layouts/main';
    /**
     * @var string
     */
    public $typeClass = 'pavlinter\admmailing\components\Type';
    /**
     * @var array|\Closure
     * example:
     * [
     *   'users' => function(){ return \pavlinter\adm\models\User::find(); },
     * ]
     * OR
     * [
     *   'users' => [
     *      'query' => function(){ return \pavlinter\adm\models\User::find(); }
     *      'label' => 'myLabel'
     *   ],
     * ]
     */
    public $typeList = [];
    /**
     * @var string|array
     * example: ['test@test.com' => 'fromName']
     * default: Yii::$app->params['adminEmailName']
     */
    public $from;
    /**
     * @var array
     * example:
     * [
     *   [
     *      'username' => 'test@test.com',
     *      'password' => 'xxxxxxxxx',
     *   ],
     *   [
     *      'class' => 'Swift_SmtpTransport',
     *      'host' => 'localhost',
     *      'username' => 'username',
     *      'password' => 'password',
     *      'port' => '587',
     *      'encryption' => 'tls',
     *   ]
     * ]
     */
    public $transport;

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $config = ArrayHelper::merge([
            'components' => [
                'manager' => [
                    'class' => 'pavlinter\admmailing\ModelManager'
                ],
            ],
        ], $config);

        parent::__construct($id, $parent, $config);
    }

    public function init()
    {
        parent::init();
        $this->registerTranslations();
        $this->initDefaultTypeList();
        $this->initDefaultTransport();

        if (empty($this->from)) {
            if (isset(Yii::$app->params['adminEmailName'])) {
                $this->from = Yii::$app->params['adminEmailName'];
            } else {
                throw new InvalidConfigException('The "from" property must be set.');
            }

        }
    }

    /**
     * @param \pavlinter\adm\Adm $adm
     */
    public function loading($adm)
    {
        if ($adm->user->can('Adm-Mailing')) {
            $adm->params['left-menu']['admmailing'] = [
                'label' => '<i class="fa fa-envelope"></i><span>' . $adm::t('menu', 'Mailing') . '</span>',
                'url' => ['/admmailing/mailing/index']
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $adm = Adm::register();
        if (!parent::beforeAction($action) || !$adm->user->can('Adm-Mailing')) {
            return false;
        }
        MailingAsset::register(Yii::$app->getView());
        return true;
    }

    /**
     * @return bool
     * @throws InvalidConfigException
     */
    public function initDefaultTransport()
    {
        if ($this->transport instanceof Closure) {
            $this->transport = call_user_func($this->transport, $this);
        }

        if ($this->transport === null) {
            return false;
        }

        if (!is_array($this->transport)) {
            throw new InvalidConfigException('The "transport" property must be array.');
        }

        /* @var \yii\swiftmailer\Mailer $mail */
        /* @var \Swift_SmtpTransport|\Swift_MailTransport $transport */
        $mailer = Yii::$app->mailer;
        $transport = $mailer->getTransport();
        if (!($transport instanceof \Swift_SmtpTransport)) {
            throw new InvalidConfigException('You mast set mailer component.');
        }

        $default = [
            'class' => 'Swift_SmtpTransport',
            'host' => $transport->getHost(),
            'username' => $transport->getUsername(),
            'password' => $transport->getPassword(),
            'port' => $transport->getPort(),
            'encryption' => $transport->getEncryption(),
        ];

        $newTransport = [$transport];
        foreach ($this->transport as $options) {
            $options = ArrayHelper::merge($default, $options);
            $newTransport[] = Yii::createObject($options);
        }
        $this->transport = $newTransport;
    }

    /**
     * @throws InvalidConfigException
     */
    public function initDefaultTypeList()
    {
        if ($this->typeList instanceof Closure) {
            $this->typeList = call_user_func($this->typeList, $this);
        }

        if (!is_array($this->typeList)) {
            throw new InvalidConfigException('The "typeList" property must be array.');
        }

        if (!isset($this->typeList['users'])) {
            $this->typeList['users'] = [
              'query' => function(){
                  return \pavlinter\adm\models\User::find();
              },
              'label' => Yii::t('adm-mailing', 'Users', ['dot' => false]),
            ];
        } else {
            if ($this->typeList['users'] === false) {
                unset($this->typeList['users']);
            }
        }

        foreach ($this->typeList as $key => $value) {
            if ($value instanceof Closure) {
                $options = [
                    'query' => $value,
                    'label' => $key,
                ];
            } else if (is_array($value)) {
                $options = $value;
            } else {
                throw new InvalidConfigException('The "typeList" property must be correct structure.');
            }

            if (!isset($options['class'])) {
                $options['class'] = $this->typeClass;
            }
            $this->typeList[$key] = Yii::createObject($options);
        }
    }

    /**
     *
     */
    public function registerTranslations()
    {
        if (!isset(Yii::$app->i18n->translations['adm-mailing*'])) {
            Yii::$app->i18n->translations['adm-mailing*'] = [
                'class' => 'pavlinter\translation\DbMessageSource',
                'forceTranslation' => true,
                'autoInsert' => true,
                'dotMode' => true,
            ];
        }
        if (!isset(Yii::$app->i18n->translations['modelAdm*'])) {
            Yii::$app->i18n->translations['modelAdm*'] = [
                'class' => 'pavlinter\translation\DbMessageSource',
                'forceTranslation' => true,
                'autoInsert' => true,
                'dotMode' => false,
            ];
        }
    }

    /**
     * @param array $options
     * @return string
     */
    public static function trasnalateLink($options = [])
    {
        $icon = ArrayHelper::remove($options, 'icon', 'glyphicon glyphicon-globe');

        if(!isset($options['class'])) {
            $options['class'] = 'pull-right';
        }
        if(!isset($options['target'])) {
            $options['target'] = '_blank';
        }
        \yii\helpers\Html::addCssClass($options, $icon);
        \yii\helpers\Html::addCssClass($options, 'mailing-trasnalate-link');

        return \yii\helpers\Html::a(null, ['/adm/source-message/index', '?' => [
            'SourceMessageSearch[category]' => 'adm-mailing'
        ],], $options);
    }
}
