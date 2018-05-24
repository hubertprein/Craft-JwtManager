<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\twigextensions;

use hubertprein\jwtmanager\JwtManager;
use hubertprein\jwtmanager\variables\JwtManagerVariable;

/**
 * JwtManager Twig Extension.
 */
class JwtManagerTwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * Return our Twig Extension name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'JWT Manager';
    }

    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        return ['jwtManager' => new JwtManagerVariable()];
    }
}
