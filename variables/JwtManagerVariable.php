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
 * JWT Manager variable.
 */
class JwtManagerVariable
{
    /**
     * @return JwtManager_JwtsVariable
     */
    public function jwts()
    {
        return new JwtManager_JwtsVariable();
    }
}
