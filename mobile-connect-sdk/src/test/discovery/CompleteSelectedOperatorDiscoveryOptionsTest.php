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

use MCSDK\discovery\CompleteSelectedOperatorDiscoveryOptions;

class CompleteSelectedOperatorDiscoveryOptionsTest extends PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $completeSelectedOperatorDiscoveryOptions = new CompleteSelectedOperatorDiscoveryOptions();

        $this->assertEquals($completeSelectedOperatorDiscoveryOptions->getTimeout(), CompleteSelectedOperatorDiscoveryOptions::DEFAULT_TIMEOUT);
        $this->assertEquals($completeSelectedOperatorDiscoveryOptions->isCookiesEnabled(), CompleteSelectedOperatorDiscoveryOptions::DEFAULT_COOKIES_ENABLED);
    }

    public function testSetAndGetTimeout()
    {
        $testTimeout = rand(238978723, 23486723673878887);

        $completeSelectedOperatorDiscoveryOptions = new CompleteSelectedOperatorDiscoveryOptions();
        $completeSelectedOperatorDiscoveryOptions->setTimeout($testTimeout);

        $this->assertEquals($testTimeout, $completeSelectedOperatorDiscoveryOptions->getTimeout());
    }

    public function testSetAndIsCookiesEnabled()
    {
        $testCookiesEnabled = rand(0, 1) == 1;

        $completeSelectedOperatorDiscoveryOptions = new CompleteSelectedOperatorDiscoveryOptions();
        $completeSelectedOperatorDiscoveryOptions->setCookiesEnabled($testCookiesEnabled);

        $this->assertEquals($testCookiesEnabled, $completeSelectedOperatorDiscoveryOptions->isCookiesEnabled());
    }

}
