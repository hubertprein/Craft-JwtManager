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
use craft\db\Query;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as JwtEngine;
use hubertprein\jwtmanager\JwtManager;
use hubertprein\jwtmanager\models\Jwt;
use hubertprein\jwtmanager\records\Jwt as JwtRecord;

/**
 * Jwts service.
 */
class Jwts extends Base
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
        $this->_currentUserAgent = JwtManager::$plugin->mobileDetect->getUserAgent();
        $this->_currentDeviceType = JwtManager::$plugin->mobileDetect->getDeviceType();
        $this->_currentBrowserType = JwtManager::$plugin->mobileDetect->getBrowserType();
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
    public function isTokenValid(string $token, string $type = ''): bool
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
        if ($jwt->type === Jwt::TYPE_LOGIN) {
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
    public function isTokenExpired(string $token): bool
    {
        try {
            $decodedJwt = JwtEngine::decode($token, $this->secretKey, ['HS256']);
        } catch (\Exception $e) {
            if ($e instanceof ExpiredException) {
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
            $decodedJwt = JwtEngine::decode($token, $this->secretKey, ['HS256']);

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
     * Get all JWTs.
     *
     * @return array
     */
    public function getAllJwts()
    {
        $jwts = [];
        foreach ($this->_createJwtQuery()->all() as $record) {
            $jwts[] = new Jwt($record);
        }

        return $jwts;
    }

    /**
     * Get a JWT.
     *
     * @param array $params DB columns and values.
     *
     * @return Jwt|null
     */
    public function getJwtBy(array $params)
    {
        $record = $this->_createJwtQuery()->where($params)->one();

        return $record ? new Jwt($record) : null;
    }

    /**
     * Get a JWT by it's ID.
     *
     * @param int $id
     *
     * @return Jwt|null
     */
    public function getJwtById(int $id)
    {
        $record = $this->_createJwtQuery()
            ->where(['jwts.id' => $id])
            ->one();

        return $record ? new Jwt($record) : null;
    }

    /**
     * Get a JWT.
     *
     * @param string $token
     * @param string $type  [Optional] Specific type.
     *
     * @return Jwt|null
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
        $record = $this->_createJwtQuery()->where($params)->one();

        return $record ? new Jwt($record) : null;
    }

    /**
     * Get a JWT for a user based on current device and browser.
     *
     * @param User   $user
     * @param string $type
     * @param bool   $newOnInvalid [Optional] Create a new JWT when none are found or JWT is invalid.
     *
     * @return Jwt|null
     */
    public function getOneJwtForUser(User $user, string $type, bool $newOnInvalid = false)
    {
        // Set search params
        $params = [
            'device' => $this->_currentDeviceType,
            'browser' => $this->_currentBrowserType,
            'userId' => $user->id,
            'type' => $type,
        ];

        // Get record
        $record = $this->_createJwtQuery()->where($params)->one();
        if ($record) {
            $jwt = new Jwt($record);

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
     * @param User   $user
     * @param string $type [Optional] Specific type.
     *
     * @return array
     */
    public function getAllJwtsForUser(User $user, string $type = ''): array
    {
        // Set search params
        $params = [
            'userId' => $user->id,
        ];

        // Specific type?
        if (!empty($type)) {
            $params['type'] = $type;
        }

        // Get JWTs
        $jwts = [];
        foreach ($this->_createJwtQuery()->where($params)->all() as $record) {
            $jwts[] = new Jwt($record);
        }

        return $jwts;
    }

    /**
     * Get a new JWT.
     *
     * @param string $type
     * @param array  $contents
     *
     * @return Jwt|null
     */
    public function getNewJwt(string $type, array $contents)
    {
        $jwt = new Jwt();
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
     * @param User   $user
     * @param string $type
     * @param array  $contents [Optional] User ID by default, add your own here.
     *
     * @return Jwt|null
     */
    public function getNewJwtByUser(User $user, string $type, array $contents = [])
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
     * @return Jwt|null
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
     * @param Jwt $jwt
     *
     * @return string|null if not found.
     */
    public function getCreatedRefreshTokenByJwt(Jwt $jwt)
    {
        return !empty($this->_createdRefreshJwts[$jwt->id]) ? $this->_createdRefreshJwts[$jwt->id]->token : null;
    }

    /**
     * Saves a JWT.
     *
     * @param Jwt $jwt
     *
     * @return bool
     */
    public function saveJwt(Jwt $jwt): bool
    {
        $isNewJwt = !$jwt->id;

        // Get JWT record
        if ($jwt->id) {
            $record = JwtRecord::find()
                ->where(['id' => $jwt->id])
                ->one();

            if (!$record) {
                $this->setError('No JWT exists with the ID “{id}”.', ['id' => $jwt->id]);
                return false;
            }
        } else {
            $record = new JwtRecord();
        }

        // Get more info on a new JWT
        if ($isNewJwt) {
            // Bound information
            $jwt->device = $this->_currentDeviceType;
            $jwt->browser = $this->_currentBrowserType;
            $jwt->userAgent = $this->_currentUserAgent;

            // Remove old JWTs for user?
            if ($jwt->userId) {
                $existingJwts = JwtRecord::find()
                    ->where([
                        'type' => $jwt->type,
                        'device' => $jwt->device,
                        'browser' => $jwt->browser,
                        'userId' => $jwt->userId,
                    ])
                    ->all();
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
        $record->userId = $jwt->userId;
        $record->relatedId = $jwt->relatedId;
        $record->type = $jwt->type;
        $record->contents = $jwt->contents;
        $record->device = $jwt->device;
        $record->browser = $jwt->browser;
        $record->userAgent = $jwt->userAgent;
        $record->token = $jwt->token;
        if (!$record->save()) {
            $this->setError('Could not save JWT.');
            return false;
        }

        // Update model ID?
        if (!$jwt->id) {
            $jwt->id = $record->id;
        }

        // Create refresh token for this JWT?
        if ($isNewJwt && $this->settings->refreshTokens && $jwt->type !== Jwt::TYPE_REFRESH) {
            $refreshJwt = new Jwt();
            $refreshJwt->relatedId = $jwt->id;
            $refreshJwt->type = Jwt::TYPE_REFRESH;
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
     * @param Jwt $jwt
     *
     * @return bool
     */
    public function refreshJwt(Jwt $jwt): bool
    {
        $jwt->token = $this->_createToken($jwt);

        return $this->saveJwt($jwt);
    }

    /**
     * Update JWT usage.
     *
     * @param Jwt $jwt
     *
     * @return bool
     */
    public function updateJwtUsage(Jwt $jwt): bool
    {
        $jwt->timesUsed++;
        $jwt->dateUsed = DateTimeHelper::currentUTCDateTime();

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
        $success = Craft::$app->getDb()->createCommand()
            ->delete('{{%jwtmanager_jwts}}', $params)
            ->execute();

        return $success ? true : false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Create a token.
     *
     * @param Jwt $jwt
     *
     * @return string
     */
    private function _createToken(Jwt $jwt)
    {
        switch ($jwt->type) {
            case Jwt::TYPE_REFRESH:
                $expireDate = strtotime($this->settings->refreshTokensExpireAfter);
                break;

            default:
                $expireDate = strtotime($this->settings->tokensExpireAfter);
                break;
        }

        $params = [
            'iss' => UrlHelper::siteUrl(), // Issuer
            'iat' => time(), // Issued date
            'exp' => $expireDate, // Expiry date
            'data' => $jwt->contents, // Payload
        ];

        return JwtEngine::encode($params, $this->secretKey);
    }

    /**
     * Create JWT query.
     *
     * @return Query
     */
    private function _createJwtQuery(): Query
    {
        return (new Query())
            ->select([
                'jwts.id',
                'jwts.userId',
                'jwts.relatedId',
                'jwts.type',
                'jwts.contents',
                'jwts.device',
                'jwts.browser',
                'jwts.userAgent',
                'jwts.token',
                'jwts.timesUsed',
                'jwts.dateUsed',
                'jwts.dateCreated',
                'jwts.dateUpdated',
            ])
            ->from(['{{%jwtmanager_jwts}} jwts'])
            ->orderBy(['id' => SORT_DESC]);
    }
}
