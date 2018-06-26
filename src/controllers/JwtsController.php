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
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Jwts controller.
 */
class JwtsController extends Controller
{
    /**
     * @var string[]
     */
    protected $allowAnonymous = [
        'use-refresh'
    ];

    /**
     * Jwts index.
     *
     * @param array $variables
     *
     * @return Response The rendering result
     */
    public function actionIndex(array $variables = []): Response
    {
        $variables['jwts'] = JwtManager::$plugin->jwts->getAllJwts();

        return $this->renderTemplate('jwt-manager/jwts/_index', $variables);
    }

    /**
     * Called when a user brings up a JWT for editing before being displayed.
     *
     * @param int|null $jwtId The JWT's ID, if editing an existing JWT.
     * @param Jwt|null $jwt   The JWT being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionEdit(int $jwtId = null, Jwt $jwt = null): Response
    {
        if ($jwtId !== null) {
            if ($jwt === null) {
                $jwt = JwtManager::$plugin->jwts->getJwtById($jwtId);

                if (!$jwt) {
                    throw new NotFoundHttpException('Jwt not found');
                }
            }

            $title = Craft::t('jwt-manager', 'Edit JWT');
        } else {
            if ($jwt === null) {
                $jwt = new Jwt();
            }

            $title = Craft::t('jwt-manager', 'Create a new JWT');
        }

        // Get JWT types
        $jwtTypes = [
            [
                'label' => Craft::t('jwt-manager', 'Login'),
                'value' => Jwt::TYPE_LOGIN
            ],
            [
                'label' => Craft::t('jwt-manager', 'One time login'),
                'value' => Jwt::TYPE_ONE_TIME_LOGIN
            ]
        ];

        return $this->renderTemplate('jwt-manager/jwts/_edit', [
            'jwt' => $jwt,
            'jwtId' => $jwtId,
            'brandNewJwt' => !$jwt->id,
            'title' => $title,
            'jwtTypes' => $jwtTypes
        ]);
    }

    /**
     * Saves a JWT.
     *
     * @return Response|null
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        // Get request service
        $request = Craft::$app->getRequest();

        // Main JWT settings
        $jwt = new Jwt();
        $jwt->type = $request->getRequiredBodyParam('type', $jwt->type);
        $jwt->userId = $request->getBodyParam('userId', $jwt->userId);
        $jwt->contents = $request->getBodyParam('contents', $jwt->contents);

        // Fix values
        if (\is_array($jwt->userId)) {
            $jwt->userId = reset($jwt->userId);
        }
        if (empty($jwt->contents)) {
            $jwt->contents = ['userId' => $jwt->userId];
        }

        // Save it!
        if (!JwtManager::$plugin->jwts->saveJwt($jwt)) {
            Craft::$app->getSession()->setError(Craft::t('jwt-manager', 'Could not save JWT.'));

            // Send the JWT back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'jwt' => $jwt
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('jwt-manager', 'JWT saved.'));

        return $this->redirectToPostedUrl($jwt);
    }

    /**
     * Deletes a JWT.
     *
     * @return Response
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $jwtId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        return $this->asJson([
            'success' => JwtManager::$plugin->jwts->deleteJwtBy(['id' => $jwtId])
        ]);
    }

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
            return $this->asJson(['token' => null]);
        }

        // Refresh the token
        if (!JwtManager::$plugin->jwts->refreshJwt($jwt)) {
            return $this->asJson(['token' => null]);
        }

        // A JWT was used
        $refreshJwt = JwtManager::$plugin->jwts->getJwtBy([
            'type' => Jwt::TYPE_REFRESH,
            'token' => $token,
        ]);
        if ($refreshJwt) {
            JwtManager::$plugin->jwts->updateJwtUsage($refreshJwt);
        }

        return $this->asJson(['token' => $jwt->token]);
    }
}
