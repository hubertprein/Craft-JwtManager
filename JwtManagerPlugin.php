<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH . 'jwtmanager/vendor/autoload.php');

class JwtManagerPlugin extends BasePlugin
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'JWT Manager';
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getDeveloper()
    {
        return 'Hubert Prein';
    }

    /**
     * @inheritdoc
     */
    public function getDeveloperUrl()
    {
        return 'https://github.com/hubertprein';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return craft()->templates->render('jwtmanager/settings', [
            'settings' => $this->getSettings()
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineSettings()
    {
        return [
            'secretKeyFormat' => [AttributeType::String, 'default' => '{{ siteName }}_login'],
            'tokensExpireAfter' => [AttributeType::String, 'default' => '+1 day'],
            'refreshTokens' => [AttributeType::Bool, 'default' => true],
            'refreshTokensExpireAfter' => [AttributeType::String, 'default' => '+2 weeks'],
        ];
    }

    /**
     * @return Twig_Extension
     */
    public function addTwigExtension()
    {
        Craft::import('plugins.jwtmanager.variables.*');
        Craft::import('plugins.jwtmanager.twigextensions.JwtManagerTwigExtension');

        return new JwtManagerTwigExtension();
    }
}
