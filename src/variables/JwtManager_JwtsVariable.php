<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\variables;

use craft\elements\User;
use hubertprein\jwtmanager\JwtManager;
use yii\di\ServiceLocator;

/**
 * Jwts variables.
 */
class JwtManager_JwtsVariable extends ServiceLocator
{
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
        return JwtManager::$plugin->jwts->isTokenValid($token, $type);
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
        return JwtManager::$plugin->jwts->isTokenExpired($token);
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
        return JwtManager::$plugin->jwts->getTokenPayload($token);
    }

    /**
     * Get all JWTs.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getAllJwts(int $limit = null, int $offset = null): array
    {
        return JwtManager::$plugin->jwts->getAllJwts($limit, $offset);
    }

    /**
     * Get total JWTs.
     *
     * @return int
     */
    public function getTotalJwts(): int
    {
        return JwtManager::$plugin->jwts->getTotalJwts();
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
        return JwtManager::$plugin->jwts->getJwtBy($params);
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
        return JwtManager::$plugin->jwts->getJwtById($id);
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
        return JwtManager::$plugin->jwts->getOneJwt($token, $type);
    }

    /**
     * Get a JWT for a user based on current device and browser.
     *
     * @param User   $user
     * @param string $type
     *
     * @return JwtManager_JwtModel|null
     */
    public function getOneJwtForUser(User $user, string $type)
    {
        return JwtManager::$plugin->jwts->getOneJwtForUser($user, $type);
    }

    /**
     * Get all JWTs for a user.
     *
     * @param User   $user
     * @param string $type [Optional] Specific type.
     *
     * @return array|null
     */
    public function getAllJwtsForUser(User $user, string $type = '')
    {
        return JwtManager::$plugin->jwts->getAllJwtsForUser($user, $type);
    }
}
