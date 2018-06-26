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

use Craft;
use craft\base\Model;
use craft\elements\User;
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

    /**
     * @var array Retrieved users.
     */
    private $_users = [];

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
     * Get JWT's type name.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        switch ($this->type) {
            case self::TYPE_LOGIN:
                return Craft::t('jwt-manager', 'Login');
            case self::TYPE_ONE_TIME_LOGIN:
                return Craft::t('jwt-manager', 'One time login');
            case self::TYPE_REFRESH:
                return Craft::t('jwt-manager', 'Refresh');
        }

        return Craft::t('jwt-manager', 'Unknown');
    }

    /**
     * Get JWT's user.
     *
     * @return null|User
     */
    public function getUser()
    {
        if (empty($this->userId)) {
            return null;
        }

        if (!isset($this->_users[$this->userId])) {
            $this->_users[$this->userId] = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return $this->_users[$this->userId];
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
