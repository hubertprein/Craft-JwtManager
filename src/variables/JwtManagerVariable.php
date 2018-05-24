<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\variables;

use yii\di\ServiceLocator;

/**
 * JwtManager variables.
 */
class JwtManagerVariable extends ServiceLocator
{
    /**
     * Init variable.
     */
    public function init()
    {
        parent::init();

        $this->set('jwts', JwtManager_JwtsVariable::class);
    }
}
