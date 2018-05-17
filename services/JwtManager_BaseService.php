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

/**
 * Base service.
 */
class JwtManager_BaseService extends BaseApplicationComponent
{
    // Properties
    // =========================================================================

    /**
     * @var Settings Plugin settings.
     */
    protected $settings;

    /**
     * @var string Secret key for JWT generation.
     */
    protected $secretKey;

    /**
     * @var string|null Current class using this base.
     */
    private $_currentClass;

    /**
     * @var array Error that occurred for this class.
     */
    private $_currentError = [];

    // Public Methods
    // =========================================================================

    /**
     * Init service.
     *
     * @return void
     */
    public function init()
    {
        // Get plugin settings
        $plugin = craft()->plugins->getPlugin('jwtmanager');
        $this->settings = $plugin->getSettings();

        // Get secret key format
        $secretKey = craft()->templates->renderString($this->settings->secretKeyFormat);
        $this->secretKey = StringHelper::toSnakeCase($secretKey);

        // Current working class
        $this->_currentClass = get_class($this);

        // Allow additional request headers
        if (!craft()->request->isCpRequest()) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Authorization");
            header('Access-Control-Allow-Methods: POST, GET');
        }

        // Skip any code when we get an OPTIONS request
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            throw new HttpException(200);
        }

        // Do we have POST data? Might be that apps are calling this, and have "post" data.
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
    }

    /**
     * Check whether an error has occurred.
     *
     * @return bool
     */
    public function hasError()
    {
        return isset($this->_currentError[$this->_currentClass]);
    }

    /**
     * Get possible error that occurred.
     *
     * @return string
     */
    public function getError()
    {
        if (isset($this->_currentError[$this->_currentClass])) {
            return $this->_currentError[$this->_currentClass];
        }

        return Craft::t('No error occurred.');
    }

    /**
     * Set error that occurred.
     *
     * @param string $message
     * @param array  $params  [Optional] These will be used to replace the corresponding placeholders in the message.
     *
     * @return void
     */
    public function setError($message, $params = [])
    {
        if (!isset($this->_currentError[$this->_currentClass])) {
            $this->_currentError[$this->_currentClass] = Craft::t($message, $params);
        }
    }
}
