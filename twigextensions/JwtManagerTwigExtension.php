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
 * JWT Manager Twig Extension.
 */
class JwtManagerTwigExtension extends \Twig_Extension
{
    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'JWT Manager';
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array
     */
    public function getGlobals()
    {
        return [
            'jwtManager' => new JwtManagerVariable(),
        ];
    }
}
