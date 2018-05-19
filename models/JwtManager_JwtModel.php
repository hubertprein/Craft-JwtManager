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
 * Jwt model.
 */
class JwtManager_JwtModel extends BaseModel
{
    // Constants
    // =========================================================================

    const TYPE_LOGIN = 'login';
    const TYPE_ONE_TIME_LOGIN = 'oneTimeLogin';
    const TYPE_REFRESH = 'refresh';

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineAttributes()
    {
        return [
            'id' => AttributeType::Number,
            'userId' => AttributeType::Number,
            'relatedId' => AttributeType::Number,
            'type' => [AttributeType::String, 'required' => true],
            'contents' => [AttributeType::Mixed, 'required' => true],
            'device' => [AttributeType::String, 'required' => true],
            'browser' => [AttributeType::String, 'required' => true],
            'userAgent' => [AttributeType::String, 'required' => true],
            'token' => [AttributeType::Mixed, 'required' => true],
            'timesUsed' => [AttributeType::Number, 'default' => 0],
            'dateUsed' => AttributeType::DateTime,
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * Whether this token is valid.
     *
     * @return bool
     */
    public function isTokenValid()
    {
        return craft()->jwtManager_jwts->isTokenValid($this->token, $this->type);
    }

    /**
     * Whether this token is expired.
     *
     * @return bool
     */
    public function isTokenExpired()
    {
        return craft()->jwtManager_jwts->isTokenExpired($this->token, $this->type);
    }
}
