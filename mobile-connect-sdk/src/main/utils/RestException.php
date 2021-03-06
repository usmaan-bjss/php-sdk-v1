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

/**
 * Class to hold data relating to an exception thrown while making a Rest call.
 *
 * The various properties may not be set.
 */
class RestException extends \Exception
{

    private $uri;
    private $statusCode;
    private $headers;
    private $contents;

    /**
     * RestException constructor.
     *
     * @param string $message the exception message
     * @param string $uri the uri called during the exception
     * @param \Exception $exception if an exception has been thrown is is passed
     * @param RestResponse|null $response response from the request
     */
    public function __construct($message, $uri = null, $exception = null, RestResponse $response = null)
    {
        if (!is_null($exception)) {
            parent::__construct($message, 0, $exception);
        } else {
            parent::__construct($message);
        }

        if (is_null($response)) {
            $this->uri = $uri;
            $this->statusCode = 0;
            $this->headers = null;
            $this->contents = null;
        } else {
            $this->uri = $response->getUri();
            $this->statusCode = $response->getStatusCode();
            $this->headers = $response->getHeaders();
            $this->contents = $response->getResponse();
        }
    }

    /**
     * The URI of the failed request.
     *
     * @return string The URI of the failing request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * The status code of the failed request.
     *
     * @return int The http status code of the failed request.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * The response Http headers of the failed request.
     *
     * @return array The response Http headers of the failed request.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * The response contents of the failed request.
     *
     * @return string The response contents of the failed request.
     */
    public function getContents()
    {
        return $this->contents;
    }

}
