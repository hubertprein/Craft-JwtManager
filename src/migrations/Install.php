<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\migrations;

use Craft;
use craft\db\Migration;
use hubertprein\jwtmanager\Jwts;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%jwtmanager_jwts}}');
    }

    /**
     * Create necessary tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%jwtmanager_jwts}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer(),
            'relatedId' => $this->integer(),
            'type' => $this->string()->notNull(),
            'contents' => $this->text()->notNull(),
            'device' => $this->string()->notNull(),
            'browser' => $this->string()->notNull(),
            'userAgent' => $this->string()->notNull(),
            'token' => $this->text()->notNull(),
            'timesUsed' => $this->integer()->defaultValue(0),
            'dateUsed' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Create necessary indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%jwtmanager_jwts}}', ['userId'], false);
        $this->createIndex(null, '{{%jwtmanager_jwts}}', ['userId', 'device', 'browser'], false);
        $this->createIndex(null, '{{%jwtmanager_jwts}}', ['relatedId'], false);
    }

    /**
     * Create necessary foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%jwtmanager_jwts}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%jwtmanager_jwts}}', ['relatedId'], '{{%jwtmanager_jwts}}', ['id'], 'CASCADE', null);
    }
}
