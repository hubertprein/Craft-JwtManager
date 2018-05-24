<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use hubertprein\jwtmanager\JwtManager;
use hubertprein\jwtmanager\models\Jwt;
use yii\web\Response;

/**
 * Auth controller.
 */
class AuthController extends Controller
{
    /**
     * @var User Current logged in user.
     */
    protected $currentUser;

    /**
     * @var string|null Found token from auto login.
     */
    protected $token;

    /**
     * @var string|null Found refresh token from auto login.
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
        if (JwtManager::$plugin->login->auto()) {
            // Get our tokens
            $this->token = JwtManager::$plugin->login->getToken();
            $this->refreshToken = JwtManager::$plugin->login->getRefreshToken();
        } else {
            // Can we find a JWT based on the current user?
            $user = Craft::$app->getUser()->getIdentity();
            if ($user) {
                // Well, we do have a user it seems.
                $jwt = JwtManager::$plugin->jwts->getOneJwtForUser($user, Jwt::TYPE_LOGIN);
                $this->token = $jwt ? $jwt->token : null;
            } else {
                // Error occurred..
                $autoLoginError = JwtManager::$plugin->login->getError();
            }
        }

        // Logged in user?
        $this->currentUser = Craft::$app->getUser()->getIdentity();
        if (!$this->currentUser) {
            $this->_terminate(($autoLoginError ? $autoLoginError : Craft::t('jwt-manager', 'Not logged in.')));
        }
    }

    /**
     * User login.
     *
     * - Send credentials through POST. credentials[username] and credentials[password].
     * - Send authorization bearer header with a JWT as value.
     *
     * @return Response
     */
    public function actionLogin(): Response
    {
        // Cast user attributes to standard class
        $attributes = $this->currentUser->getAttributes();
        $stdClass = new \stdClass($attributes);
        foreach ($attributes as $key => $value) {
            $stdClass->{$key} = $value;
        }
        $stdClass->photoUrl = $this->currentUser->getThumbUrl(100);

        return $this->asJson([
            'token' => $this->token,
            'refreshToken' => $this->refreshToken,
            'user' => $stdClass,
        ]);
    }

    /**
     * User logout.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Craft::$app->getUser()->logout();

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Terminate the application.
     *
     * @param string $message
     * @param int    $status  [Optional] HTTP status code.
     */
    private function _terminate(string $message, int $status = 401)
    {
        // Find our server protocol
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

        // Display error message
        header($protocol.' '.$status.' '.$message);
        echo $message;
        Craft::$app->end();
    }
}
