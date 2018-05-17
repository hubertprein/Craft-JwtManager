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
 * Jwt record.
 */
class JwtManager_JwtRecord extends BaseRecord
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineAttributes()
    {
        return [
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
     * @inheritdoc
     */
    public function getTableName()
    {
        return 'jwtmanager_jwts';
    }

    /**
     * @inheritdoc
     */
    public function defineRelations()
    {
        return [
            'user' => [static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE],
            'related' => [static::BELONGS_TO, 'JwtManager_JwtRecord', 'onDelete' => static::CASCADE],
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['userId']],
            ['columns' => ['userId', 'device', 'browser']],
            ['columns' => ['relatedId']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scopes()
    {
        return [
            'ordered' => [
                'order' => 'dateCreated desc'
            ]
        ];
    }
}
