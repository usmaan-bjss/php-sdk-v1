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

namespace MCSDK\helpers;

class ResponseJson
{

    private $_status;
    private $_action;
    private $_parameter1;
    private $_parameter2;
    private $_jsonArray;

    /**
     * ResponseJson constructor.
     *
     * @param string $status the status of the last rest response
     * @param string $action the action to be taken by the script receiving this response
     * @param string $parameter1 a parameter, usually a url, to be used
     * @param string $parameter2 an additional parameter, usually a url, to be used
     */
    public function __construct($status, $action, $parameter1, $parameter2 = null)
    {
        $this->_status = $status;
        $this->_action = $action;
        $this->_parameter1 = $parameter1;
        $this->_parameter2 = $parameter2;

        $this->_jsonArray = array('status' => $status,
            'action' => $action,
            'parameter1' => $parameter1,
            'parameter2' => $parameter2);
    }

    /**
     * get the set status parameter
     *
     * @return string the status
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * get the action
     *
     * @return string the action to be taken
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * get the parameter1 value
     *
     * @return string the parameter1, usually a url
     */
    public function getParameter1()
    {
        return $this->_parameter1;
    }

    /**
     * almost always null, used for edge cases
     *
     * @return null|string get the parameter2
     */
    public function getParameter2()
    {
        return $this->_parameter2;
    }

    /**
     * Get the JSON string for the entire list of variables defined in this class
     *
     * @return string The JSON encoded string of class level variables; name value pairs
     */
    public function asString()
    {
        return json_encode($this->_jsonArray);
    }

}
