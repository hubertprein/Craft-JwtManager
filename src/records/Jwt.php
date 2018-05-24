<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\records;

use craft\db\ActiveRecord;

/**
 * Jwt record.
 *
 * @property int    $id         ID.
 * @property int    $userId     User ID.
 * @property int    $relatedId  Related JWT ID.
 * @property string $type       JWT type.
 * @property string $contents   JWT contents.
 * @property string $device     Request device.
 * @property string $browser    Request browser.
 * @property string $userAgent  Request user-agent.
 * @property string $token      The actual JWT.
 * @property string $timesUsed  How many times the JWT was used.
 * @property string $dateUsed   Used on.
 *
 * @since 1.0.0
 */
class Jwt extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%jwtmanager_jwts}}';
    }
}
