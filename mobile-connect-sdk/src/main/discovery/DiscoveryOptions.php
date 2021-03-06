<?php

/**
 *                          SOFTWARE USE PERMISSION
 *
 *  By downloading and accessing this software and associated documentation
 *  files ("Software") you are granted the unrestricted right to deal in the
 *  Software, including, without limitation the right to use, copy, modify,
 *  publish, sublicense and grant such rights to third parties, subject to the
 *  following conditions:
 *
 *  The following copyright notice and this permission notice shall be included
 *  in all copies, modifications or substantial portions of this Software:
 *  Copyright © 2016 GSM Association.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS," WITHOUT WARRANTY OF ANY KIND, INCLUDING
 *  BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
 *  IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE. YOU AGREE TO INDEMNIFY AND HOLD HARMLESS THE AUTHORS AND COPYRIGHT
 *  HOLDERS FROM AND AGAINST ANY SUCH LIABILITY.
 */

namespace MCSDK\discovery;

/**
 * All the values are optional.
 *
 * Class DiscoveryOptions
 * @package MCSDK\discovery
 */
class DiscoveryOptions
{

    /**
     * Default timeout for the Rest call in milliseconds.
     */
    const DEFAULT_TIMEOUT = 30000;

    /**
     * Default value for manually select.
     */
    const DEFAULT_MANUALLY_SELECT = false;

    /**
     * Default value for cookies enabled.
     */
    const DEFAULT_COOKIES_ENABLED = true;

    private $_manuallySelect;
    private $_identifiedMCC;
    private $_identifiedMNC;
    private $_cookiesEnabled;
    private $_usingMobileData;
    private $_localClientIP;
    private $_timeout;
    private $_clientIP;

    /**
     * DiscoveryOptions constructor.
     */
    public function __construct()
    {
        $this->_timeout = self::DEFAULT_TIMEOUT;
        $this->_manuallySelect = self::DEFAULT_MANUALLY_SELECT;
        $this->_cookiesEnabled = self::DEFAULT_COOKIES_ENABLED;
    }

    /**
     * Get manually select.
     *
     * @return True if manual select requested.
     */
    public function isManuallySelect()
    {
        return $this->_manuallySelect;
    }

    /**
     * Set manually select.
     *
     * @param bool $newValue New manually select value.
     */
    public function setManuallySelect($newValue)
    {
        $this->_manuallySelect = $newValue;
    }

    /**
     * Return the Identified Mobile Country Code.
     *
     * @return string The Mobile Country Code value.
     */
    public function getIdentifiedMCC()
    {
        return $this->_identifiedMCC;
    }

    /**
     * Set the Identified Mobile Country Code.
     *
     * @param string $newValue New Mobile Country Code value.
     */
    public function setIdentifiedMCC($newValue)
    {
        $this->_identifiedMCC = $newValue;
    }

    /**
     * Return the Identified Mobile Network Code.
     *
     * @return string The Mobile Network Code value.
     */
    public function getIdentifiedMNC()
    {
        return $this->_identifiedMNC;
    }

    /**
     * Set the Identified Mobile Network Code.
     *
     * @param string $newValue New Mobile Network Code value.
     */
    public function setIdentifiedMNC($newValue)
    {
        $this->_identifiedMNC = $newValue;
    }

    /**
     * Are cookies to be stored/sent.
     *
     * @return True if cookies are to be sent.
     */
    public function isCookiesEnabled()
    {
        return $this->_cookiesEnabled;
    }

    /**
     * Are cookies to be stored/sent.
     *
     * @param bool $newValue True if cookies are to be sent.
     */
    public function setCookiesEnabled($newValue)
    {
        $this->_cookiesEnabled = $newValue;
    }

    /**
     * Return whether the application is using mobile data.
     *
     * @return True if the application is using mobile data.
     */
    public function isUsingMobileData()
    {
        return $this->_usingMobileData;
    }

    /**
     * Specify whether the application is using mobile data.
     *
     * Set to "true" if your application is able to determine that the user is
     * accessing the service via mobile data. This tells the Discovery Service
     * to discover using the mobile-network.
     *
     * @param bool $newValue New value.
     */
    public function setUsingMobileData($newValue)
    {
        $this->_usingMobileData = $newValue;
    }

    /**
     * The current local IP address of the client application i.e. the actual
     * IP address currently allocated to the device running the application.
     *
     * This can be used within header injection processes from the MNO to
     * confirm the application is directly using a mobile data connection from
     * the consumption device rather than MiFi/WiFi to mobile hotspot.
     *
     * @return string The actual IP address of the client application.
     */
    public function getLocalClientIP()
    {
        return $this->_localClientIP;
    }

    /**
     * The current local IP address of the client application i.e. the actual IP
     * address currently allocated to the device running the application.
     *
     * This can be used within header injection processes from the MNO to
     * confirm the application is directly using a mobile data connection from
     * the consumption device rather than MiFi/WiFi to mobile hotspot.
     *
     * @param string $newValue The actual IP address of the client application.
     */
    public function setLocalClientIP($newValue)
    {
        $this->_localClientIP = $newValue;
    }

    /**
     * Return the timeout value used by the SDK for the Discovery Rest request.
     *
     * @return int The timeout to be used.
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Set the timeout of a Discovery request.
     *
     * The timeout (in milliseconds) to be used by the SDK when making a
     * Discovery request.
     *
     * @param int $newValue New timeout value.
     */
    public function setTimeout($newValue)
    {
        $this->_timeout = $newValue;
    }

    /**
     *  Allows a server application to indicate the 'public IP address' of the
     *  connection from a client application/mobile browser to the server.
     *
     *  This is used in place of the public IP address normally detected by the
     * discovery service. Note this will usually differ from the Local-Client-IP
     * address, and the public IP address detected by the application server
     * should not be used for the Local-Client-IP address.
     *
     * @return string The client IP
     */
    public function getClientIP()
    {
        return $this->_clientIP;
    }

    /**
     *  Allows a server application to indicate the 'public IP address' of the
     *  connection from a client application/mobile browser to the server.
     *
     *  This is used in place of the public IP address normally detected by the
     *  discovery service. Note this will usually differ from the
     *  Local-Client-IP address, and the public IP address detected by the
     *  application server should not be used for the Local-Client-IP address.
     *
     * @param string $clientIP The client IP as detected by the server.
     */
    public function setClientIP($clientIP)
    {
        $this->_clientIP = $clientIP;
    }

}
