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

use MCSDK\discovery\ParsedDiscoveryRedirect;

class ParsedDiscoveryRedirectTest extends PHPUnit_Framework_TestCase
{

    private $selectedMCC;
    private $selectedMNC;
    private $encryptedMSISDN;
    private $parsedDiscoveryRedirect;

    public function __construct()
    {
        $this->parsedDiscoveryRedirect = new ParsedDiscoveryRedirect($this->selectedMCC, $this->selectedMNC, $this->encryptedMSISDN);

        parent::__construct();
    }

    public function testGetSelectedMCC()
    {
        $this->assertEquals($this->selectedMCC, $this->parsedDiscoveryRedirect->getSelectedMCC());
    }

    public function testGetSelectedMNC()
    {
        $this->assertEquals($this->selectedMNC, $this->parsedDiscoveryRedirect->getSelectedMNC());
    }

    public function testGetEncryptedMSISDN()
    {
        $this->assertEquals($this->encryptedMSISDN, $this->parsedDiscoveryRedirect->getEncryptedMSISDN());
    }

    public function testHasDataWithNullMCCAndMNC()
    {
        $parsedDiscoveryRedirect = new ParsedDiscoveryRedirect(null, null, null);

        $this->assertFalse($parsedDiscoveryRedirect->hasMCCAndMNC());
    }

    public function testHasDataWithNotNullMCCAndNullMNC()
    {
        $testCC = 'GB';

        $parsedDiscoveryRedirect = new ParsedDiscoveryRedirect($testCC, null, null);

        $this->assertFalse($parsedDiscoveryRedirect->hasMCCAndMNC());
    }

    public function testHasDataWithNullMCCAndNotNullMNC()
    {
        $testNC = '+44';

        $parsedDiscoveryRedirect = new ParsedDiscoveryRedirect(null, $testNC, null);

        $this->assertFalse($parsedDiscoveryRedirect->hasMCCAndMNC());
    }

    public function testHasDataActual()
    {
        $testCC = 'GB';
        $testNC = '+44';

        $parsedDiscoveryRedirect = new ParsedDiscoveryRedirect($testCC, $testNC, null);

        $this->assertTrue($parsedDiscoveryRedirect->hasMCCAndMNC());
    }

}
