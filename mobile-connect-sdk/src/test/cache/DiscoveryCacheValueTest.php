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
require_once(dirname(__FILE__) . '/../bootstrap.php');

use MCSDK\cache\DiscoveryCacheValue;

class DiscoveryCacheValueTest extends PHPUnit_Framework_TestCase
{

    private $discoveryCacheValue;

    public function __construct()
    {
        $this->discoveryCacheValue = new \stdClass();
        $this->discoveryCacheValue->{MCSDK\utils\Constants::SUBSCRIBER_ID_FIELD_NAME} = 987;
        $this->discoveryCacheValue->{MCSDK\utils\Constants::CLIENT_ID_FIELD_NAME} = 654;

        parent::__construct();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructTestNullTTLThrowsException()
    {
        new DiscoveryCacheValue(null, $this->discoveryCacheValue);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructTestNullValueThrowsException()
    {
        new DiscoveryCacheValue(new \DateTime(), null);
    }

    public function testGetTtl()
    {
        $ttl = new \DateTime();
        $discoveryCacheValue = new DiscoveryCacheValue($ttl, $this->discoveryCacheValue);

        $this->assertEquals($discoveryCacheValue->getTtl(), $ttl);
    }

    public function testGetValue()
    {
        $discoveryCacheValue = new DiscoveryCacheValue(new \DateTime(), $this->discoveryCacheValue);

        $this->assertEquals($discoveryCacheValue->getValue(), $this->discoveryCacheValue);
    }

    public function testHasExpiredTrueCondition()
    {
        $expiredDateTime = new \DateTime();
        $expiredDateTime->sub(new \DateInterval('P1D'));

        $discoveryCacheValue = new DiscoveryCacheValue($expiredDateTime, $this->discoveryCacheValue);
        $this->assertTrue($discoveryCacheValue->hasExpired());
    }

    public function testHasExpiredFalseCondition()
    {
        $expiredDateTime = new \DateTime();
        $expiredDateTime->add(new \DateInterval('P1D'));

        $discoveryCacheValue = new DiscoveryCacheValue($expiredDateTime, $this->discoveryCacheValue);
        $this->assertFalse($discoveryCacheValue->hasExpired());
    }

}
