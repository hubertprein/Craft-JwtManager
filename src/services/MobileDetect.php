<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\services;

use Craft;
use Mobile_Detect;
use yii\base\Component;

/**
 * Mobile detect service.
 */
class MobileDetect extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var Mobile_Detect
     */
    private $_mobileDetect = null;

    // Public Methods
    // =========================================================================

    /**
     * Init service.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Mobile Detect handler
        $this->_mobileDetect = new Mobile_Detect();
    }

    /**
     * Get device type based on current request.
     *
     * @return string
     */
    public function getDeviceType(): string
    {
        if (!$this->isMobile()) {
            if ($this->is('Electron')) {
                return 'desktop';
            } elseif ($this->getBrowserType() !== 'desktop') {
                return 'web';
            }
        } elseif ($this->isTablet()) {
            return 'tablet';
        } elseif ($this->isPhone()) {
            return 'phone';
        }

        return 'unknown';
    }

    /**
     * Get browser type based on current request.
     *
     * @return string
     */
    public function getBrowserType(): string
    {
        // Get available browsers
        $browsers = $this->_mobileDetect->getBrowsers();

        // Find possible browser
        $found = 'desktop';
        foreach ($browsers as $name => $regex) {
            if ($this->is($name)) {
                $found = $name;
            }
        }

        return $found;
    }

    /**
     * Get user-agent.
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        return $this->_mobileDetect->getUserAgent();
    }

    /**
     * Set user-agent.
     *
     * @param string $userAgent [Optional] Specific UA or current found by default.
     *
     * @return string|null
     */
    public function setUserAgent($userAgent = null)
    {
        return $this->_mobileDetect->setUserAgent($userAgent);
    }

    /**
     * Get HTTP headers.
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        return $this->_mobileDetect->getHttpHeaders();
    }

    /**
     * Set HTTP headers.
     *
     * @param array $httpHeaders [Optional] Specific HTTP headers or current found by default.
     *
     * @return bool
     */
    public function setHttpHeaders($httpHeaders = null)
    {
        return $this->_mobileDetect->setHttpHeaders($httpHeaders);
    }

    /**
     * Whether the request comes from a mobile device (including tablets!).
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        return $this->_mobileDetect->isMobile();
    }

    /**
     * Whether the request comes from a tablet only.
     *
     * @return bool
     */
    public function isTablet(): bool
    {
        return $this->_mobileDetect->isTablet();
    }

    /**
     * Whether the request comes from a phone only.
     *
     * @return bool
     */
    public function isPhone(): bool
    {
        return $this->isMobile() && !$this->isTablet();
    }

    /**
     * Test anything.
     *
     * E.g.: is('iphone')
     *
     * @param string $key
     * @param string $userAgent   [Optional] Specific UA or current found by default.
     * @param array  $httpHeaders [Optional] Specific HTTP headers or current found by default.
     *
     * @return bool|int|null
     */
    public function is(string $key, $userAgent = null, $httpHeaders = null)
    {
        return $this->_mobileDetect->is($key, $userAgent, $httpHeaders);
    }

    /**
     * Regex match.
     *
     * @param string $pattern
     * @param string $userAgent [Optional] Specific UA or current found by default.
     *
     * @return bool
     */
    public function match(string $pattern, $userAgent = null): bool
    {
        return $this->_mobileDetect->match($pattern, $userAgent);
    }
}
