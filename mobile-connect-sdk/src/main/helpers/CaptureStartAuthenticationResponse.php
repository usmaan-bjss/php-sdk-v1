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

use MCSDK\oidc\IStartAuthenticationCallback;
use MCSDK\oidc\StartAuthenticationResponse;

class CaptureStartAuthenticationResponse implements IStartAuthenticationCallback
{

    private $_startAuthenticationResponse;

    /**
     * Returns the initial authentication response, prior to the redirect
     *
     * @return StartAuthenticationResponse
     */
    public function getStartAuthenticationResponse()
    {
        return $this->_startAuthenticationResponse;
    }

    /**
     * Called to finalise the authentication process
     *
     * @param StartAuthenticationResponse $startAuthenticationResponse
     */
    public function complete(StartAuthenticationResponse $startAuthenticationResponse)
    {
        $this->_startAuthenticationResponse = $startAuthenticationResponse;
    }

}
