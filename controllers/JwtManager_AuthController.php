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
 * Auth controller.
 */
class JwtManager_AuthController extends BaseController
{
    /**
     * @var UserModel Current logged in user.
     */
    protected $currentUser;

    /**
     * @var array Found token from auto login.
     */
    protected $token;

    /**
     * @var array Found refresh token from auto login.
     */
    protected $refreshToken;

    /**
     * @var bool Allow all requests as we will ensure that we require a user.
     */
    protected $allowAnonymous = true;

    /**
     * Init controller.
     */
    public function init()
    {
        parent::init();

        // Let's see if we can login!
        $autoLoginError = null;
        if (craft()->jwtManager_login->auto()) {
            // Get our tokens
            $this->token = craft()->jwtManager_login->getToken();
            $this->refreshToken = craft()->jwtManager_login->getRefreshToken();
        } else {
            // Can we find a JWT based on the current user?
            $user = craft()->userSession->getUser();
            if ($user) {
                // Well, we do have a user it seems.
                $jwt = craft()->jwtManager_jwts->getOneJwtForUser($user, JwtManager_JwtModel::TYPE_LOGIN);
                $this->token = $jwt ? $jwt->token : null;
            } else {
                // Error occurred..
                $autoLoginError = craft()->jwtManager_login->getError();
            }
        }

        // Logged in user?
        $this->currentUser = craft()->userSession->getUser();
        if (!$this->currentUser) {
            $this->_terminate(($autoLoginError ? $autoLoginError : Craft::t('Not logged in.')));
        }
    }

    /**
     * User login.
     *
     * - Send credentials through POST. credentials[username] and credentials[password].
     * - Send authorization bearer header with a JWT as value.
     */
    public function actionLogin()
    {
        // Cast user attributes to standard class
        $attributes = $this->currentUser->getAttributes();
        $stdClass = new \stdClass($attributes);
        foreach ($attributes as $key => $value) {
            $stdClass->{$key} = $value;
        }
        $stdClass->photoUrl = $this->currentUser->getThumbUrl(100);

        return $this->returnJson([
            'token' => $this->token,
            'refreshToken' => $this->refreshToken,
            'user' => $stdClass,
        ]);
    }

    /**
     * User logout.
     */
    public function actionLogout()
    {
        craft()->userSession->logout(false);

        return $this->returnJson([
            'success' => true
        ]);
    }

    /**
     * Terminate the application.
     *
     * @param string $message
     * @param int    $status  [Optional] HTTP status code.
     */
    private function _terminate($message, $status = 401)
    {
        // Find our server protocol
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

        // Display error message
        header($protocol . ' ' . $status . ' ' . $message);
        echo $message;
        craft()->end();
    }
}
