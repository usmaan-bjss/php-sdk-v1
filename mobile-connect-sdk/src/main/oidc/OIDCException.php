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

namespace MCSDK\oidc;

/**
 * Exception thrown by methods in {@link IOIDC}.
 *
 * Not all properties may be set.
 */
class OIDCException extends \Exception
{

    private $_uri;
    private $_responseCode;
    private $_headers;
    private $_contents;

    /**
     * OIDCException constructor.
     *
     * @param string $message the exception message
     * @param \Exception $exception the exception thrown. Deprecated.
     * @param string $uri the uri called during the exception
     * @param int $responseCode the http response code from the rest call
     * @param array $headers the headers from the rest call
     * @param string $contents the contents of the response
     */
    public function __construct($message, $exception = null, $uri = null, $responseCode = 0, array $headers = null, $contents = null)
    {
        parent::__construct($message);

        $this->_uri = $uri;
        $this->_responseCode = $responseCode;
        $this->_headers = $headers;
        $this->_contents = $contents;
    }

    /**
     * The uri of a failed Rest call.
     *
     * Optional.
     *
     * @return string The rest uri.
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * The http response code of a failed Rest call.
     *
     * Optional.
     *
     * @return int The http response code.
     */
    public function getResponseCode()
    {
        return $this->_responseCode;
    }

    /**
     * The http response headers of a failed Rest call.
     *
     * Optional.
     *
     * @return array The response http headers.
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * The http contents of a failed Rest call.
     *
     * Optional.
     *
     * @return string The http response contents.
     */
    public function getContents()
    {
        return $this->_contents;
    }

}
