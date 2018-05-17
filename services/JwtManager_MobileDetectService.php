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
 * Mobile detect service.
 */
class JwtManager_MobileDetectService extends BaseApplicationComponent
{
    // Properties
    // =========================================================================

    /**
     * @var \Mobile_Detect
     */
    private $_mobileDetect = null;

    // Public Methods
    // =========================================================================

    /**
     * @return \Mobile_Detect|null
     */
    public function getMobileDetect()
    {
        if ($this->_mobileDetect === null) {
            $this->_mobileDetect = new \Mobile_Detect();
        }

        return $this->_mobileDetect;
    }

    /**
     * Get device type based on current request.
     *
     * @return string
     */
    public function getDeviceType()
    {
        if (!$this->isMobile()) {
            return 'desktop';
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
    public function getBrowserType()
    {
        // Get available browsers
        $browsers = $this->getMobileDetect()->getBrowsers();

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
        return $this->getMobileDetect()->getUserAgent();
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
        return $this->getMobileDetect()->setUserAgent($userAgent);
    }

    /**
     * Get HTTP headers.
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        return $this->getMobileDetect()->getHttpHeaders();
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
        return $this->getMobileDetect()->setHttpHeaders($httpHeaders);
    }

    /**
     * Whether the request comes from a mobile device (including tablets!).
     *
     * @return bool
     */
    public function isMobile()
    {
        return $this->getMobileDetect()->isMobile();
    }

    /**
     * Whether the request comes from a tablet only.
     *
     * @return bool
     */
    public function isTablet()
    {
        return $this->getMobileDetect()->isTablet();
    }

    /**
     * Whether the request comes from a phone only.
     *
     * @return bool
     */
    public function isPhone()
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
    public function is($key, $userAgent = null, $httpHeaders = null)
    {
        return $this->getMobileDetect()->is($key, $userAgent, $httpHeaders);
    }

    /**
     * Regex match.
     *
     * @param string $pattern
     * @param string $userAgent [Optional] Specific UA or current found by default.
     *
     * @return bool
     */
    public function match($pattern, $userAgent = null)
    {
        return $this->getMobileDetect()->match($pattern, $userAgent);
    }
}
