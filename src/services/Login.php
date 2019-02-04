<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\services;

use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;
use hubertprein\jwtmanager\JwtManager;
use hubertprein\jwtmanager\models\Jwt;
use yii\web\HttpException;

/**
 * Login service.
 */
class Login extends Base
{
    // Properties
    // =========================================================================

    /**
     * @var Jwt|null Found JWT.
     */
    private $_foundJwt;

    // Public Methods
    // =========================================================================

    /**
     * Get token.
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->_foundJwt ? $this->_foundJwt->token : null;
    }

    /**
     * Get refresh token.
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->_foundJwt ? JwtManager::$plugin->jwts->getCreatedRefreshTokenByJwt($this->_foundJwt) : null;
    }

    /**
     * Try to login by several methods.
     *
     * @return bool
     */
    public function auto(): bool
    {
        // Get required request information
        $credentials = Craft::$app->getRequest()->getBodyParam('credentials');

        // Login by credentials
        if ($credentials && is_array($credentials) && isset($credentials['username']) && isset($credentials['password'])) {
            return $this->loginByCredentials($credentials['username'], $credentials['password']);
        }

        // Login by JWT
        $token = JwtManager::$plugin->jwts->getTokenFromRequest();
        if ($token) {
            return $this->loginByToken($token);
        }

        // No action found.
        $this->setError('Could not find any login information.');

        return false;
    }

    /**
     * Login using a token.
     *
     * @param string $token
     *
     * @return bool
     */
    public function loginByToken(string $token): bool
    {
        // Token valid?
        if (!JwtManager::$plugin->jwts->isTokenValid($token, Jwt::TYPE_LOGIN)) {
            // One time access token used?
            if (JwtManager::$plugin->jwts->isTokenValid($token, Jwt::TYPE_ONE_TIME_LOGIN)) {
                // It can not be expired..
                if (!JwtManager::$plugin->jwts->isTokenExpired($token)) {
                    // Get the payload
                    $payload = JwtManager::$plugin->jwts->getTokenPayload($token);
                    if ($payload) {
                        // Create new JWT
                        $jwt = JwtManager::$plugin->jwts->getNewJwtByUserId($payload->userId, Jwt::TYPE_LOGIN);
                        if ($jwt) {
                            // Delete the one time access token
                            JwtManager::$plugin->jwts->deleteJwtBy([
                                'type' => Jwt::TYPE_ONE_TIME_LOGIN,
                                'userId' => $payload->userId,
                                'token' => $token,
                            ]);

                            // Try to login again!
                            return $this->loginByToken($jwt->token);
                        }
                    }
                }
            }

            // Nope.. Error.
            $this->setError(JwtManager::$plugin->jwts->getError());
            return false;
        }

        // Can we get the payload?
        $payload = JwtManager::$plugin->jwts->getTokenPayload($token);
        if (!$payload) {
            // Nope.. Error.
            $this->setError(JwtManager::$plugin->jwts->getError());
            return false;
        }

        // Login user!
        if (!Craft::$app->getUser()->loginByUserId($payload->userId)) {
            // Unknown error
            return $this->_handleFailure();
        }

        return $this->_handleSuccess(false);
    }

    /**
     * Login using credentials.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function loginByCredentials(string $username, string $password, bool $rememberMe = false): bool
    {
        // Does a user exist with that username/email?
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($username);
        if (!$user) {
            return $this->_handleFailure(User::AUTH_INVALID_CREDENTIALS);
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$user->authenticate($password)) {
            return $this->_handleFailure($user->authError);
        }

        // Get the session duration
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        // Try logging them in
        if (!Craft::$app->getUser()->login($user, $duration)) {
            // Unknown error
            return $this->_handleFailure();
        }

        return $this->_handleSuccess();
    }

    // Private Methods
    // =========================================================================

    /**
     * Successful login.
     *
     * @param bool $token [Optional] Create a new token for logged in user.
     *
     * @return bool
     */
    private function _handleSuccess(bool $createNewToken = true): bool
    {
        // Get logged in user
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            // Weird.. Should not happen but we still don't seem to have a user
            return $this->_handleFailure();
        }

        // Create new JWT?
        if ($createNewToken) {
            $jwt = JwtManager::$plugin->jwts->getNewJwtByUser($user, Jwt::TYPE_LOGIN);
            if (!$jwt) {
                $this->setError(JwtManager::$plugin->jwts->getError());
                return false;
            }
            $this->_foundJwt = $jwt;
        } else {
            // Do we have a JWT for this user?
            $jwt = JwtManager::$plugin->jwts->getOneJwtForUser($user, Jwt::TYPE_LOGIN);
            if ($jwt && $jwt->isTokenValid() && !$jwt->isTokenExpired()) {
                $this->_foundJwt = $jwt;

                // A JWT was used
                JwtManager::$plugin->jwts->updateJwtUsage($this->_foundJwt);
            } else {
                // Create a new one
                $jwt = JwtManager::$plugin->jwts->getNewJwtByUser($user, Jwt::TYPE_LOGIN);
                if (!$jwt) {
                    $this->setError(JwtManager::$plugin->jwts->getError());
                    return false;
                }
                $this->_foundJwt = $jwt;
            }
        }

        return true;
    }

    /**
     * Unsuccessful login.
     *
     * @param string $authError Error that occurred.
     *
     * @return bool
     */
    private function _handleFailure(string $authError = ''): bool
    {
        switch ($authError) {
            case User::AUTH_PENDING_VERIFICATION:
                $message = Craft::t('app', 'Account has not been activated.');
                break;
            case User::AUTH_ACCOUNT_LOCKED:
                $message = Craft::t('app', 'Account locked.');
                break;
            case User::AUTH_ACCOUNT_COOLDOWN:
                $message = Craft::t('app', 'Account locked.');
                break;
            case User::AUTH_PASSWORD_RESET_REQUIRED:
                $message = Craft::t('app', 'You need to reset your password, but an error was encountered when sending the password reset email.');
                break;
            case User::AUTH_ACCOUNT_SUSPENDED:
                $message = Craft::t('app', 'Account suspended.');
                break;
            case User::AUTH_NO_CP_ACCESS:
                $message = Craft::t('app', 'You cannot access the CP with that account.');
                break;
            case User::AUTH_NO_CP_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the CP while the system is offline with that account.');
                break;
            case User::AUTH_NO_SITE_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the site while the system is offline with that account.');
                break;
            default:
                if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
                    $message = Craft::t('app', 'Invalid email or password.');
                } else {
                    $message = Craft::t('app', 'Invalid username or password.');
                }
        }
        $this->setError($message);

        return false;
    }
}
