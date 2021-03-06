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

use MCSDK\utils\RestException;
use MCSDK\utils\RestResponse;

class RestExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructWithResponse()
    {
        $message = 'Some test exception message';
        $uri = 'http://someexception.com';
        $exception = new \Exception();
        $response = new RestResponse($uri, 202, new Zend\Http\Headers(), 'some response string');

        $restException = new RestException($message, $uri, $exception, $response);

        $this->assertEquals($restException->getUri(), $uri);
        $this->assertEquals($restException->getStatusCode(), 202);
        $this->assertEquals($restException->getHeaders(), new Zend\Http\Headers());
        $this->assertEquals($restException->getContents(), $response->getResponse());
    }

    public function testConstructWithNullResponse()
    {
        $message = 'Some test exception message';
        $uri = 'http://someexception.com';
        $exception = new \Exception();

        $restException = new RestException($message, $uri, $exception, null);

        $this->assertEquals($restException->getUri(), $uri);
        $this->assertEquals($restException->getStatusCode(), 0);
        $this->assertNull($restException->getHeaders());
        $this->assertNull($restException->getContents());
    }

    public function testConstructWithNullResponseAndNullException()
    {
        $message = 'Some test exception message';
        $uri = 'http://someexception.com';

        $restException = new RestException($message, $uri, null, null);

        $this->assertEquals($restException->getUri(), $uri);
        $this->assertEquals($restException->getStatusCode(), 0);
        $this->assertNull($restException->getHeaders());
        $this->assertNull($restException->getContents());
    }
}