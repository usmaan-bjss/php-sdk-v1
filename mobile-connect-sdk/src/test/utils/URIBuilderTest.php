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

use MCSDK\utils\URIBuilder;

class URIBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testConstructSetsDefaults()
    {
        $testURI = 'http://uriBuilderTestUri.com';

        $uriBuilder = new URIBuilder($testURI);

        $this->assertEquals($uriBuilder->build(), $testURI . urlencode('?'));
    }

    public function testAddParamsAndBuildMethods()
    {
        $testURI = 'http://anotherUriBuilderTestUri.com';

        $uriBuilder = new URIBuilder($testURI);
        $uriBuilder->addParameter('testingQueryKey', 'testingQueryValue');
        $uriBuilder->addParameter('testingQueryKey2', 'testingQueryValue2');

        $this->assertEquals($uriBuilder->build(), $testURI .
            urlencode('?testingQueryKey=testingQueryValue&testingQueryKey2=testingQueryValue2'));
    }
}