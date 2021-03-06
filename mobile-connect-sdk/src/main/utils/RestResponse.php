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

namespace MCSDK\utils;

use Zend\Http\Headers;

/**
 * Class to hold the response from making a Rest call.
 */
class RestResponse
{

    private $uri;
    private $statusCode;
    private $headers;
    private $response;
    private $jsonContent;

    /**
     * RestResponse constructor.
     *
     * @param string $uri the called uri
     * @param int $statusCode the response http code
     * @param \Zend\Http\Headers $headers the response headers
     * @param string $response the response body
     */
    public function __construct($uri, $statusCode, Headers $headers, $response)
    {
        $this->jsonContent = false;

        $this->uri = $uri;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->response = $response;

        $this->jsonContent = false;
        if ((!is_null($headers)) && (!is_null($headers->toArray()))) {
            foreach ($headers->toArray() as $key => $value) {
                if (strcasecmp(Constants::CONTENT_TYPE_HEADER_NAME, $key) == 0) {
                    $this->jsonContent = stripos($value, Constants::ACCEPT_JSON_HEADER_VALUE) !== false;
                    break;
                }
            }
        }
    }

    /**
     * Test that the string appears to be JSON, note - this will return true for integers as well.
     *
     * @return bool true if json false if not
     */
    public function isJsonContent()
    {
        return $this->jsonContent;
    }

    /**
     * Return the response data.
     *
     * @return string The response data.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return the uri of the request.
     *
     * @return string The uri of the request
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return the status code of the response
     *
     * @return int The status code of the response
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return the response Http headers
     *
     * @return Headers The response Http headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

}
