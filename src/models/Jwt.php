<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\models;

use craft\base\Model;
use hubertprein\jwtmanager\JwtManager;

/**
 * Jwt model.
 */
class Jwt extends Model
{
    // Constants
    // =========================================================================

    const TYPE_LOGIN = 'login';
    const TYPE_ONE_TIME_LOGIN = 'oneTimeLogin';
    const TYPE_REFRESH = 'refresh';

    // Properties
    // =========================================================================

    /**
     * @var int|null ID.
     */
    public $id;

    /**
     * @var int|null User ID.
     */
    public $userId;

    /**
     * @var int|null Related JWT id.
     */
    public $relatedId;

    /**
     * @var string|null JWT type.
     */
    public $type;

    /**
     * @var string|null JWT contents.
     */
    public $contents;

    /**
     * @var string|null Request device.
     */
    public $device;

    /**
     * @var string|null Request browser.
     */
    public $browser;

    /**
     * @var string|null Request user-agent.
     */
    public $userAgent;

    /**
     * @var string|null The actual JWT.
     */
    public $token;

    /**
     * @var int How many times the JWT was used.
     */
    public $timesUsed = 0;

    /**
     * @var \DateTime|null Used on.
     */
    public $dateUsed;

    /**
     * @var \DateTime|null Created on.
     */
    public $dateCreated;

    /**
     * @var \DateTime|null Updated on.
     */
    public $dateUpdated;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'userId', 'timesUsed'], 'number', 'integerOnly' => true],
            [['userId', 'type', 'contents', 'device', 'browser', 'userAgent', 'token'], 'required'],
            [['active'], 'boolean'],
            [
                ['type'],
                'in',
                'range' => [
                    self::TYPE_LOGIN,
                    self::TYPE_ONE_TIME_LOGIN,
                    self::TYPE_REFRESH,
                ],
            ],
        ];
    }

    /**
     * Whether this token is valid.
     *
     * @return bool
     */
    public function isTokenValid(): bool
    {
        return JwtManager::$plugin->jwts->isTokenValid($this->token, $this->type);
    }

    /**
     * Whether this token is expired.
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        return JwtManager::$plugin->jwts->isTokenExpired($this->token, $this->type);
    }
}
