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
 * Tokens controller.
 */
class JwtManager_TokensController extends BaseController
{
    /**
     * @var bool Allow all requests in this controller.
     */
    protected $allowAnonymous = true;

    /**
     * Use a refresh token.
     *
     * When the refresh token is valid, the actual token it should refresh will be returned.
     * If the token could not be refreshed, no token will be returned.
     *
     * @return mixed
     */
    public function actionUseRefresh()
    {
        // Do we have a token?
        $token = craft()->jwtManager_jwts->getTokenFromRequest();
        if (!$token) {
            return $this->returnJson(['token' => null]);
        }

        // Token valid?
        if (!craft()->jwtManager_jwts->isTokenValid($token, JwtManager_JwtModel::TYPE_REFRESH)) {
            return $this->returnJson(['token' => null]);
        }

        // Can we get the payload?
        $payload = craft()->jwtManager_jwts->getTokenPayload($token);
        if (!$payload) {
            return $this->returnJson(['token' => null]);
        }

        // Can we find the related JWT?
        if (!isset($payload->relatedId)) {
            return $this->returnJson(['token' => null]);
        }
        $jwt = craft()->jwtManager_jwts->getJwtById($payload->relatedId);
        if (!$jwt) {
            return $this->returnJson(['jwt' => null]);
        }

        // Refresh the token
        if (!craft()->jwtManager_jwts->refreshJwt($jwt)) {
            return $this->returnJson(['jwt' => null]);
        }

        // A JWT was used
        $refreshJwt = craft()->jwtManager_jwts->getJwtBy([
            'type' => JwtManager_JwtModel::TYPE_REFRESH,
            'token' => $token,
        ]);
        if ($refreshJwt) {
            craft()->jwtManager_jwts->updateJwtUsage($refreshJwt);
        }

        return $this->returnJson(['jwt' => $jwt->token]);
    }
}
