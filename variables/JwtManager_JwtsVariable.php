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
 * Jwts variable.
 */
class JwtManager_JwtsVariable
{
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
        return craft()->jwtManager_jwts->isTokenValid($token, $type);
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
        return craft()->jwtManager_jwts->isTokenExpired($token);
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
        return craft()->jwtManager_jwts->getTokenPayload($token);
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
        return craft()->jwtManager_jwts->getJwtBy($params);
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
        return craft()->jwtManager_jwts->getJwtById($id);
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
        return craft()->jwtManager_jwts->getOneJwt($token, $type);
    }

    /**
     * Get a JWT for a user based on current device and browser.
     *
     * @param UserModel $user
     * @param string    $type
     *
     * @return JwtManager_JwtModel|null
     */
    public function getOneJwtForUser(UserModel $user, string $type)
    {
        return craft()->jwtManager_jwts->getOneJwtForUser($user, $type);
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
        return craft()->jwtManager_jwts->getAllJwtsForUser($user, $type);
    }
}
