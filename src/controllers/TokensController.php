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
use craft\web\Controller;
use hubertprein\jwtmanager\JwtManager;
use hubertprein\jwtmanager\models\Jwt;
use yii\web\Response;

/**
 * Tokens controller.
 */
class TokensController extends Controller
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
     * @return Response
     */
    public function actionUseRefresh(): Response
    {
        // Do we have a token?
        $token = JwtManager::$plugin->jwts->getTokenFromRequest();
        if (!$token) {
            return $this->asJson(['token' => null]);
        }

        // Token valid?
        if (!JwtManager::$plugin->jwts->isTokenValid($token, Jwt::TYPE_REFRESH)) {
            return $this->asJson(['token' => null]);
        }

        // Can we get the payload?
        $payload = JwtManager::$plugin->jwts->getTokenPayload($token);
        if (!$payload) {
            return $this->asJson(['token' => null]);
        }

        // Can we find the related JWT?
        if (!isset($payload->relatedId)) {
            return $this->asJson(['token' => null]);
        }
        $jwt = JwtManager::$plugin->jwts->getJwtById($payload->relatedId);
        if (!$jwt) {
            return $this->asJson(['jwt' => null]);
        }

        // Refresh the token
        if (!JwtManager::$plugin->jwts->refreshJwt($jwt)) {
            return $this->asJson(['jwt' => null]);
        }

        // A JWT was used
        $refreshJwt = JwtManager::$plugin->jwts->getJwtBy([
            'type' => Jwt::TYPE_REFRESH,
            'token' => $token,
        ]);
        if ($refreshJwt) {
            JwtManager::$plugin->jwts->updateJwtUsage($refreshJwt);
        }

        return $this->asJson(['jwt' => $jwt->token]);
    }
}
