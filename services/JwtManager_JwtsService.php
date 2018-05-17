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

use \Firebase\JWT\JWT;

/**
 * Jwts service.
 */
class JwtManager_JwtsService extends JwtManager_BaseService
{
    // Properties
    // =========================================================================

    /**
     * @var array Created refresh JWTs.
     */
    private $_createdRefreshJwts = [];

    /**
     * @var string|null Current request user-agent.
     */
    private $_currentUserAgent;

    /**
     * @var string|null Current request device type.
     */
    private $_currentDeviceType;

    /**
     * @var string|null Current request browser type.
     */
    private $_currentBrowserType;

    // Public Methods
    // =========================================================================

    /**
     * Init service.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Get request information
        $this->_currentUserAgent = craft()->jwtManager_mobileDetect->getUserAgent();
        $this->_currentDeviceType = craft()->jwtManager_mobileDetect->getDeviceType();
        $this->_currentBrowserType = craft()->jwtManager_mobileDetect->getBrowserType();
    }

    /**
     * Get the token from current request.
     *
     * @return string|null if not found.
     */
    public function getTokenFromRequest()
    {
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Validate a token.
     *
     * @param string $token
     * @param string $type  [Optional] Validate for specific type.
     *
     * @return bool
     */
    public function isTokenValid(string $token, string $type = '')
    {
        // Get JWT based on token
        $jwt = $this->getOneJwt($token, $type);

        // Basic checks
        if (!$jwt) {
            $this->setError('The JWT could not be found.');
            return false;
        } elseif (!empty($type) && $jwt->type !== $type) {
            $this->setError('The JWT has an incorrect type.');
            return false;
        }

        // Additional checks, if we'd like to login
        if ($jwt->type === JwtManager_JwtModel::TYPE_LOGIN) {
            if (strcmp($jwt->device, $this->_currentDeviceType) !== 0) {
                $this->setError('The JWT is not bound to this device.');
                return false;
            } elseif (strcmp($jwt->browser, $this->_currentBrowserType) !== 0) {
                $this->setError('The JWT is not bound to this device.');
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether a token is expired.
     *
     * @param string $token
     *
     * @return bool
     */
    public function isTokenExpired(string $token)
    {
        try {
            $decodedJwt = JWT::decode($token, $this->secretKey, ['HS256']);
        } catch (\Exception $e) {
            if ($e instanceof \Firebase\JWT\ExpiredException) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decode a token and return the payload.
     *
     * @param string $token
     *
     * @return stdClass|null
     */
    public function getTokenPayload(string $token)
    {
        try {
            // Attempt to get payload
            $decodedJwt = JWT::decode($token, $this->secretKey, ['HS256']);

            // Do we have any data?
            if (isset($decodedJwt->data) && !empty($decodedJwt->data)) {
                return $decodedJwt->data;
            }

            // Uh oh..
            $this->setError('The JWT has no data available.');
        } catch (\Exception $e) {
            // Unexpected value, verification failed or expired
            $this->setError($e->getMessage());
        }

        return null;
    }

    /**
     * Get a JWT.
     *
     * @param array $params DB columns and values.
     *
     * @return JwtManager_JwtModel|null
     */
    public function getJwtBy(array $params)
    {
        $record = JwtManager_JwtRecord::model()->findByAttributes($params);
        if ($record) {
            return JwtManager_JwtModel::populateModel($record);
        }

        return null;
    }

    /**
     * Get a JWT by it's ID.
     *
     * @param int $id
     *
     * @return JwtManager_JwtModel|null
     */
    public function getJwtById(int $id)
    {
        $record = JwtManager_JwtRecord::model()->findById($id);
        if ($record) {
            return JwtManager_JwtModel::populateModel($record);
        }

        return null;
    }

    /**
     * Get a JWT.
     *
     * @param string $token
     * @param string $type  [Optional] Specific type.
     *
     * @return JwtManager_JwtModel|null
     */
    public function getOneJwt(string $token, string $type = '')
    {
        // Set search params
        $params = [
            'token' => $token,
        ];

        // Specific type?
        if (!empty($type)) {
            $params['type'] = $type;
        }

        // Get record
        $record = JwtManager_JwtRecord::model()->findByAttributes($params);
        if ($record) {
            return JwtManager_JwtModel::populateModel($record);
        }

        return null;
    }

    /**
     * Get a JWT for a user based on current device and browser.
     *
     * @param UserModel $user
     * @param string    $type
     * @param bool      $newOnInvalid [Optional] Create a new JWT when none are found or JWT is invalid.
     *
     * @return JwtManager_JwtModel|null
     */
    public function getOneJwtForUser(UserModel $user, string $type, bool $newOnInvalid = false)
    {
        // Set search params
        $params = [
            'device' => $this->_currentDeviceType,
            'browser' => $this->_currentBrowserType,
            'userId' => $user->id,
            'type' => $type,
        ];

        // Get record
        $record = JwtManager_JwtRecord::model()->findByAttributes($params);
        if ($record) {
            $jwt = JwtManager_JwtModel::populateModel($record);

            // Create new on invalid?
            if ($newOnInvalid && (!$this->isTokenValid($jwt->token) || $this->isTokenExpired($jwt->token))) {
                return $this->getNewJwtByUser($user, $type);
            }

            return $jwt;
        }

        // Create new on empty?
        if ($newOnInvalid) {
            return $this->getNewJwtByUser($user, $type);
        }

        return null;
    }

    /**
     * Get all JWTs for a user.
     *
     * @param UserModel $user
     * @param string    $type [Optional] Specific type.
     *
     * @return array|null
     */
    public function getAllJwtsForUser(UserModel $user, string $type = '')
    {
        // Set search params
        $params = [
            'userId' => $user->id,
        ];

        // Specific type?
        if (!empty($type)) {
            $params['type'] = $type;
        }

        // Get records
        $records = JwtManager_JwtRecord::model()->findAllByAttributes($params);
        if ($records) {
            return JwtManager_JwtModel::populateModels($records);
        }

        return null;
    }

    /**
     * Get a new JWT.
     *
     * @param string $type
     * @param array  $contents
     *
     * @return JwtManager_JwtModel|null
     */
    public function getNewJwt(string $type, array $contents)
    {
        $jwt = new JwtManager_JwtModel();
        $jwt->type = $type;
        $jwt->contents = $contents;

        // User given?
        if (!empty($contents['userId'])) {
            $jwt->userId = $contents['userId'];
        }

        if ($this->saveJwt($jwt)) {
            return $jwt;
        }

        return null;
    }

    /**
     * Get a new JWT for a user.
     *
     * @param UserModel $user
     * @param string    $type
     * @param array     $contents [Optional] User ID by default, add your own here.
     *
     * @return JwtManager_JwtModel|null
     */
    public function getNewJwtByUser(UserModel $user, string $type, array $contents = [])
    {
        return $this->getNewJwtByUserId($user->id, $type, $contents);
    }

    /**
     * Get a new JWT for a user by its ID.
     *
     * @param int    $userId
     * @param string $type
     * @param array  $contents [Optional] User ID by default, add your own here.
     *
     * @return JwtManager_JwtModel|null
     */
    public function getNewJwtByUserId(int $userId, string $type, array $contents = [])
    {
        // Add custom data
        $contents['userId'] = $userId;

        return $this->getNewJwt($type, $contents);
    }

    /**
     * Get created refresh token by a JWT.
     *
     * @param JwtManager_JwtModel $jwt
     *
     * @return string|null if not found.
     */
    public function getCreatedRefreshTokenByJwt(JwtManager_JwtModel $jwt)
    {
        return !empty($this->_createdRefreshJwts[$jwt->id]) ? $this->_createdRefreshJwts[$jwt->id]->token : null;
    }

    /**
     * Saves a JWT.
     *
     * @param JwtManager_JwtModel $jwt
     *
     * @return bool
     */
    public function saveJwt(JwtManager_JwtModel $jwt)
    {
        $isNewJwt = !$jwt->id;

        // Get JWT record
        if ($jwt->id) {
            $record = JwtManager_JwtRecord::model()->findById($jwt->id);

            if (!$record) {
                $this->setError('No JWT exists with the ID “{id}”.', ['id' => $jwt->id]);
                return false;
            }
        } else {
            $record = new JwtManager_JwtRecord();
        }

        // Get more info on a new JWT
        if ($isNewJwt) {
            // Bound information
            $jwt->device = $this->_currentDeviceType;
            $jwt->browser = $this->_currentBrowserType;
            $jwt->userAgent = $this->_currentUserAgent;

            // Remove old JWTs for user?
            if ($jwt->userId) {
                $existingJwts = JwtManager_JwtRecord::model()->findAllByAttributes([
                    'type' => $jwt->type,
                    'device' => $jwt->device,
                    'browser' => $jwt->browser,
                    'userId' => $jwt->userId,
                ]);
                if ($existingJwts) {
                    foreach ($existingJwts as $existingJwt) {
                        $existingJwt->delete();
                    }
                }
            }

            // Get a token
            $jwt->token = $this->_createToken($jwt);
        }

        // Save it!
        $record->setAttributes($jwt->getAttributes(), false);
        if (!$record->save()) {
            $this->setError('Could not save JWT.');
            return false;
        }

        // Update model ID?
        if (!$jwt->id) {
            $jwt->id = $record->id;
        }

        // Create refresh token for this JWT?
        if ($isNewJwt && $this->settings->refreshTokens && $jwt->type !== JwtManager_JwtModel::TYPE_REFRESH) {
            $refreshJwt = new JwtManager_JwtModel();
            $refreshJwt->relatedId = $jwt->id;
            $refreshJwt->type = JwtManager_JwtModel::TYPE_REFRESH;
            $refreshJwt->contents = ['relatedId' => $jwt->id];
            if ($this->saveJwt($refreshJwt)) {
                $this->_createdRefreshJwts[$jwt->id] = $refreshJwt;
            }
        }

        return true;
    }

    /**
     * Refreshes a JWT.
     *
     * @param JwtManager_JwtModel $jwt
     *
     * @return bool
     */
    public function refreshJwt(JwtManager_JwtModel $jwt)
    {
        $jwt->token = $this->_createToken($jwt);

        return $this->saveJwt($jwt);
    }

    /**
     * Update JWT usage.
     *
     * @param JwtManager_JwtModel $jwt
     *
     * @return bool
     */
    public function updateJwtUsage(JwtManager_JwtModel $jwt)
    {
        $jwt->timesUsed++;
        $jwt->dateUsed = new DateTime(null, new \DateTimeZone(craft()->getTimeZone()));

        return $this->saveJwt($jwt);
    }

    /**
     * Delete a JWT.
     *
     * @param array $params DB columns and values.
     *
     * @return bool
     */
    public function deleteJwtBy(array $params)
    {
        if (craft()->db->createCommand()->delete('jwtmanager_jwts', $params)) {
            return true;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Create a token.
     *
     * @param JwtManager_JwtModel $jwt
     *
     * @return string
     */
    private function _createToken(JwtManager_JwtModel $jwt)
    {
        switch ($jwt->type) {
            case JwtManager_JwtModel::TYPE_REFRESH:
                $expireDate = strtotime($this->settings->refreshTokensExpireAfter);
                break;

            default:
                $expireDate = strtotime($this->settings->tokensExpireAfter);
                break;
        }

        $params = [
            'iss' => craft()->getSiteUrl(), // Issuer
            'iat' => time(), // Issued date
            'exp' => $expireDate, // Expiry date
            'data' => $jwt->contents, // Payload
        ];

        return JWT::encode($params, $this->secretKey);
    }
}
