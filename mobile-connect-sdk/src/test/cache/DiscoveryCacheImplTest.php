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

use MCSDK\cache\DiscoveryCacheImpl;
use MCSDK\cache\DiscoveryCacheKey;
use MCSDK\cache\DiscoveryCacheValue;

class DiscoveryCacheImplTest extends PHPUnit_Framework_TestCase
{

    const DISCOVERY_CACHE_INSTANCE = 'Zend\Cache\Storage\Adapter\Filesystem';

    private $discoveryCache;
    private $discoveryCacheValidKey;
    private $discoveryCacheValidValue;
    private $discoveryCacheRemovableKey;
    private $selectedMCC;
    private $selectedMNC;
    private $identifiedMCC;
    private $identifiedMNC;
    private $selectedMCC2;
    private $selectedMNC2;
    private $identifiedMCC2;
    private $identifiedMNC2;

    public function __construct()
    {
        $this->discoveryCache = new DiscoveryCacheImpl();

        $this->selectedMCC = 'GB';
        $this->selectedMNC = '+44';
        $this->identifiedMCC = 'US';
        $this->identifiedMNC = '+23';

        $this->discoveryCacheValidKey = DiscoveryCacheKey::newWithSelectedData($this->selectedMCC, $this->selectedMNC);

        $this->selectedMCC2 = 'GB';
        $this->selectedMNC2 = '+45';
        $this->identifiedMCC2 = 'US';
        $this->identifiedMNC2 = '+30';

        $this->discoveryCacheRemovableKey = DiscoveryCacheKey::newWithSelectedData($this->selectedMCC2, $this->selectedMNC2);

        $ttl = new \DateTime("now");
        $ttl->add(new \DateInterval('P1D'));
        $value = new \stdClass();
        $value->{MCSDK\utils\Constants::SUBSCRIBER_ID_FIELD_NAME} = 123;
        $value->{MCSDK\utils\Constants::CLIENT_ID_FIELD_NAME} = 456;
        $this->discoveryCacheValidValue = new DiscoveryCacheValue($ttl, $value);

        parent::__construct();
    }

    public function testConstruct()
    {
        $discoveryCache = new DiscoveryCacheImpl();

        $this->assertNotNull($discoveryCache->getCache());
    }

    public function testGetCache()
    {
        $this->assertInstanceOf(self::DISCOVERY_CACHE_INSTANCE, $this->discoveryCache->getCache());
    }

    public function testAddAndGetWithValue()
    {
        $this->discoveryCache->add($this->discoveryCacheValidKey, $this->discoveryCacheValidValue);
        $this->assertEquals($this->discoveryCache->get($this->discoveryCacheValidKey), $this->discoveryCacheValidValue);
    }

    public function testAddAndGetWithExpiredValue()
    {
        $this->selectedMCC = 'US';
        $this->selectedMNC = '+03';

        $discoveryCacheExpiredKey = DiscoveryCacheKey::newWithSelectedData($this->selectedMCC, $this->selectedMNC);

        $ttl = new \DateTime("now");
        $ttl->sub(new \DateInterval('P1D'));
        $value = new \stdClass();
        $value->{MCSDK\utils\Constants::SUBSCRIBER_ID_FIELD_NAME} = 789;
        $value->{MCSDK\utils\Constants::CLIENT_ID_FIELD_NAME} = 000;
        $discoveryCacheExpiredValue = new DiscoveryCacheValue($ttl, $value);

        $this->discoveryCache->add($discoveryCacheExpiredKey, $discoveryCacheExpiredValue);
        $this->assertNull($this->discoveryCache->get($discoveryCacheExpiredKey));
    }

    public function testRemove()
    {
        $this->discoveryCache->add($this->discoveryCacheRemovableKey, $this->discoveryCacheValidValue);
        $this->assertNotNull($this->discoveryCache->get($this->discoveryCacheRemovableKey));

        $this->discoveryCache->remove($this->discoveryCacheRemovableKey);
        $this->assertNull($this->discoveryCache->get($this->discoveryCacheRemovableKey));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNullKeyThrowsExpection()
    {
        $this->discoveryCache->add(null, $this->discoveryCacheValidValue);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNullValueThrowsExpection()
    {
        $selectedMCC = 'GB';
        $selectedMNC = '+46';

        $discoveryCacheValidKey = DiscoveryCacheKey::newWithSelectedData($selectedMCC, $selectedMNC);

        $this->discoveryCache->add($discoveryCacheValidKey, null);
    }

    public function testCacheClear()
    {
        $this->assertNotNull($this->discoveryCache->get($this->discoveryCacheValidKey));
        $this->discoveryCache->clear();
        $this->assertNull($this->discoveryCache->get($this->discoveryCacheValidKey));
    }

    public function __destruct()
    {
        $this->discoveryCache->clear();
    }

}
