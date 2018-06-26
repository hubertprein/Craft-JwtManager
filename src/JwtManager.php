<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use hubertprein\jwtmanager\models\Jwt;
use hubertprein\jwtmanager\models\Settings;
use hubertprein\jwtmanager\twigextensions\JwtManagerTwigExtension;
use hubertprein\jwtmanager\variables\JwtManagerVariable;
use yii\base\Event;

/**
 * Class JwtManager.
 *
 * @property Settings                                      $settings     The plugin's settings.
 * @property \hubertprein\jwtmanager\services\Base         $base         The base service.
 * @property \hubertprein\jwtmanager\services\Jwts         $jwts         The JWTs service.
 * @property \hubertprein\jwtmanager\services\Login        $login        The login service.
 * @property \hubertprein\jwtmanager\services\MobileDetect $mobileDetect The mobile detect service.
 * @method Settings getSettings()
 */
class JwtManager extends Plugin
{
    /**
     * @var JwtManager
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * Init plugin.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register stuff
        $this->_registerRoutes();
        $this->_registerServices();
        $this->_registerTwigExtensions();
        $this->_registerVariables();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('jwt-manager/settings', [
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * Register Plugin routes.
     *
     * @return void
     */
    private function _registerRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $rules = [
                'jwt-manager/jwts' => 'jwt-manager/jwts/index',
                'jwt-manager/jwts/new' => 'jwt-manager/jwts/edit',
                'jwt-manager/jwts/edit/<jwtId:\d+>' => 'jwt-manager/jwts/edit',
            ];

            $event->rules = array_merge($event->rules, $rules);
        });
    }

    /**
     * Register Plugin services.
     */
    private function _registerServices()
    {
        $this->setComponents([
            'base' => services\Base::class,
            'jwts' => services\Jwts::class,
            'login' => services\Login::class,
            'mobileDetect' => services\MobileDetect::class,
        ]);
    }

    /**
     * Register Craft Twig Extensions.
     *
     * @return void
     */
    private function _registerTwigExtensions()
    {
        if (Craft::$app->request->getIsSiteRequest()) {
            Craft::$app->view->registerTwigExtension(new JwtManagerTwigExtension());
        }
    }

    /**
     * Register Craft variables.
     *
     * @return void
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('jwtManager', JwtManagerVariable::class);
        });
    }
}
