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
 * Class to hold a Rest error response
 */
class ErrorResponse
{

    private $_error;
    private $_error_description;
    private $_error_uri;

    /**
     * The error code.
     *
     * @return int The error code.
     */
    public function get_error()
    {
        return $this->_error;
    }

    /**
     * Set the error code.
     *
     * @param string $error The error code.
     */
    public function set_error($error)
    {
        $this->_error = $error;
    }

    /**
     * The error description.
     *
     * @return string The error description.
     */
    public function get_error_description()
    {
        return $this->_error_description;
    }

    /**
     * Set the error description.
     *
     * @param string $error_description The error description.
     */
    public function set_error_description($error_description)
    {
        $this->_error_description = $error_description;
    }

    /**
     * The error uri.
     *
     * @return string The error uri
     */
    public function get_error_uri()
    {
        return $this->_error_uri;
    }

    /**
     * Set the error uri
     *
     * @param string $error_uri The error uri
     */
    public function set_error_uri($error_uri)
    {
        $this->_error_uri = $error_uri;
    }

}
