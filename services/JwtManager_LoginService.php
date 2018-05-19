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
 * Login service.
 */
class JwtManager_LoginService extends JwtManager_BaseService
{
    // Properties
    // =========================================================================

    /**
     * @var JwtManager_JwtModel|null Found JWT.
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
        return $this->_foundJwt ? craft()->jwtManager_jwts->getCreatedRefreshTokenByJwt($this->_foundJwt) : null;
    }

    /**
     * Try to login by several methods.
     *
     * @return bool
     */
    public function auto()
    {
        // Get required request information
        $credentials = craft()->request->getPost('credentials');

        // Login by credentials
        if ($credentials && is_array($credentials) && isset($credentials['username']) && isset($credentials['password'])) {
            return $this->loginByCredentials($credentials['username'], $credentials['password']);
        }

        // Login by JWT
        $token = craft()->jwtManager_jwts->getTokenFromRequest();
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
    public function loginByToken(string $token)
    {
        // Token valid?
        if (!craft()->jwtManager_jwts->isTokenValid($token, JwtManager_JwtModel::TYPE_LOGIN)) {
            // One time access token used?
            if (craft()->jwtManager_jwts->isTokenValid($token, JwtManager_JwtModel::TYPE_ONE_TIME_LOGIN)) {
                // It can not be expired..
                if (!craft()->jwtManager_jwts->isTokenExpired($token)) {
                    // Get the payload
                    $payload = craft()->jwtManager_jwts->getTokenPayload($token);
                    if ($payload) {
                        // Create new JWT
                        $jwt = craft()->jwtManager_jwts->getNewJwtByUserId($payload->userId, JwtManager_JwtModel::TYPE_LOGIN);
                        if ($jwt) {
                            // Delete the one time access token
                            craft()->jwtManager_jwts->deleteJwtBy([
                                'type' => JwtManager_JwtModel::TYPE_ONE_TIME_LOGIN,
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
            $this->setError(craft()->jwtManager_jwts->getError());
            return false;
        }

        // Can we get the payload?
        $payload = craft()->jwtManager_jwts->getTokenPayload($token);
        if (!$payload) {
            // Nope.. Error.
            $this->setError(craft()->jwtManager_jwts->getError());
            return false;
        }

        // Login user!
        if (!craft()->userSession->loginByUserId($payload->userId)) {
            // Try to find the loginName, so we can get a specific error message
            $user = craft()->users->getUserById($payload->userId);
            return $this->_handleFailure(($user ? $user->email : null));
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
    public function loginByCredentials(string $username, string $password)
    {
        // Login user!
        if (craft()->userSession->login($username, $password)) {
            return $this->_handleSuccess();
        }

        return $this->_handleFailure($username);
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
    private function _handleSuccess(bool $createNewToken = true)
    {
        // Get logged in user
        $user = craft()->userSession->getUser();
        if (!$user) {
            // Weird.. Should not happen but we still don't seem to have a user
            return $this->_handleFailure();
        }

        // Create new JWT?
        if ($createNewToken) {
            $jwt = craft()->jwtManager_jwts->getNewJwtByUser($user, JwtManager_JwtModel::TYPE_LOGIN);
            if (!$jwt) {
                $this->setError(craft()->jwtManager_jwts->getError());
                return false;
            }
            $this->_foundJwt = $jwt;
        } else {
            // Do we have a JWT for this user?
            $jwt = craft()->jwtManager_jwts->getOneJwtForUser($user, JwtManager_JwtModel::TYPE_LOGIN);
            if ($jwt && $jwt->isTokenValid() && !$jwt->isTokenExpired()) {
                $this->_foundJwt = $jwt;

                // A JWT was used
                craft()->jwtManager_jwts->updateJwtUsage($this->_foundJwt);
            } else {
                // Create a new one
                $jwt = craft()->jwtManager_jwts->getNewJwtByUser($user, JwtManager_JwtModel::TYPE_LOGIN);
                if (!$jwt) {
                    $this->setError(craft()->jwtManager_jwts->getError());
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
     * @param string loginName Username that tried to login.
     *
     * @return bool
     */
    private function _handleFailure(string $loginName = '')
    {
        // Do we have a specific error code?
        $errorCode = craft()->userSession->getLoginErrorCode();

        // Log our failure
        $this->setError(craft()->userSession->getLoginErrorMessage($errorCode, $loginName));

        return false;
    }
}
