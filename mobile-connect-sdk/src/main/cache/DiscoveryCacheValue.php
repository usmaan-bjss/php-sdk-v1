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

namespace MCSDK\cache;

/**
 * Value that can be stored in an instance of the IDiscoveryCache.
 */
class DiscoveryCacheValue
{

    private $_ttl;
    private $_value;

    /**
     * Create an instance of DiscoveryCacheValue.
     *
     * @param \DateTime $ttl The time-to-live value. (Required).
     * @param \stdClass $value The discovery response.
     * @throws \InvalidArgumentException
     */
    public function __construct(\DateTime $ttl = null, \stdClass $value)
    {
        if (null == $ttl) {
            throw new \InvalidArgumentException("ttl cannot be null");
        }

        $this->_ttl = $ttl;
        $this->_value = $value;
    }

    /**
     * The time-to-live value of the entry.
     *
     * @return \DateTime The time-to-live value of the entry.
     */
    public function getTtl()
    {
        return $this->_ttl;
    }

    /**
     * The discovery response.
     *
     * @return \stdClass The cached discovery response.
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Has the entry expired?
     * Compares the ttl value against the current time.
     *
     * @return True if the value has exceeded its ttl value, false otherwise.
     */
    public function hasExpired()
    {
        return ($this->_ttl < new \DateTime());
    }

}
