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

namespace MCSDK\discovery;

use MCSDK\utils\TimeoutOptions;

/**
 * Interface for discovering operator end points that are used for OpenID
 * Connect authorization.
 */
interface IDiscovery
{

    /**
     * Allows an application to conduct discovery based on the predetermined
     * operator/network identified operator semantics.
     *
     * If the operator cannot be identified the function will return the
     * 'operator selection' form of the response. The application can then
     * determine how to proceed i.e. open the operator selection page separately
     * or otherwise handle this.
     *
     * The operator selection functionality will display a series of pages that
     * enables the user to identify an operator, the results are passed back to
     * the current application as parameters on the redirect URL.
     *
     * Valid discovery responses can be cached and this method can return cached
     * data.
     *
     * @param string $clientId The registered application client id. (Required).
     * @param string $clientSecret The registered application client secret.
     *        (Required).
     * @param string $discoveryURL The URL of the discovery end point. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. (Required).
     * @param DiscoveryOptions $options Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @param array $currentCookies the current list of cookies to be used over rest
     * @throws DiscoveryException
     */
    public function startAutomatedOperatorDiscovery($clientId, $clientSecret,
                                                    $discoveryURL, $redirectURL, DiscoveryOptions $options,
                                                    IDiscoveryResponseCallback $callback, array $currentCookies);

    /**
     * Similar to startAutomatedOperatorDiscovery but uses the preferences
     * as a point of reference
     *
     * @param IPreferences $preferences Instance of IPreferences that provides clientId,
     *        clientSecret and discoveryURL. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. (Required).
     * @param DiscoveryOptions $options Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @param array $currentCookies the current list of cookies to be used over rest
     * @throws DiscoveryException
     */
    public function startAutomatedOperatorDiscoveryByPreferences(
        IPreferences $preferences, $redirectURL, DiscoveryOptions $options,
        IDiscoveryResponseCallback $callback, array $currentCookies);

    /**
     * Allows an application to get the URL for the operator selection UI of the
     * discovery service.
     *
     * This will not reference the discovery result cache.
     *
     * The returned URL will contain a session id created by the discovery
     * server. The URL must be used as-is.
     *
     * @param string $clientId The registered application client id. (Required).
     * @param string $clientSecret The registered application client secret.
     *        (Required).
     * @param string $discoveryURL The URL of the discovery end point. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. (Required).
     * @param TimeoutOptions $specifiedOptions Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @throws DiscoveryException
     */
    public function getOperatorSelectionURL($clientId, $clientSecret,
                                            $discoveryURL, $redirectURL, TimeoutOptions $specifiedOptions,
                                            IDiscoveryResponseCallback $callback);

    /**
     * A convenience version of {@link 
     * IDiscovery#getOperatorSelectionURL(String, String, String, String,
     * TimeoutOptions, IDiscoveryResponseCallback)} where the clientId,
     * clientSecret and discoveryURL parameters are read from an IPreferences
     * implementation.
     *
     * @param IPreferences $preferences Instance of IPreferences that provides clientId,
     *        clientSecret and discoveryURL. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. (Required).
     * @param TimeoutOptions $specifiedOptions Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @throws DiscoveryException
     */
    public function getOperatorSelectionURLByPreferences(
        IPreferences $preferences, $redirectURL,
        TimeoutOptions $specifiedOptions, IDiscoveryResponseCallback $callback);

    /**
     * Allows an application to obtain parameters which have been passed within
     * a discovery redirect URL.
     *
     * The function will parse the redirectURL and parse out the components
     * expected for discovery i.e.
     *
     *  - selectedMCC
     *  - selectedMNC
     *  - encryptedMSISDN
     *
     * @param string $redirectURL The URL which has been subject to redirection from
     *        the discovery service. (Required).
     * @param IParsedDiscoveryRedirectCallback $callback Used to capture the response. (Required).
     * @throws \Exception
     */
    public function parseDiscoveryRedirect($redirectURL,
                                           IParsedDiscoveryRedirectCallback $callback);

    /**
     * Allows an application to use the selected operator MCC and MNC to obtain
     * the discovery response.
     *
     * In the case there is already a discovery result in the cache and the
     * Selected-MCC/Selected-MNC in the new request are the same as relates to
     * the discovery result for the cached result, the cached result will be
     * returned.
     *
     * If the operator cannot be identified by the discovery service the
     * function will return the 'operator selection' form of the response.
     *
     * @param string $clientId The registered application client id. (Required).
     * @param string $clientSecret The registered application client secret.
     *        (Required).
     * @param string $discoveryURL The URL of the discovery end point. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. If not specified http://localhost is assumed.
     *        (Optional).
     * @param string $selectedMCC The MCC of the selected operator. (Required).
     * @param string $selectedMNC The MNC of the selected operator. (Required).
     * @param CompleteSelectedOperatorDiscoveryOptions $options Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @param array $currentCookies the cookies to persist over rest services
     * @throws DiscoveryException
     */
    public function completeSelectedOperatorDiscovery($clientId, $clientSecret,
                                                      $discoveryURL, $redirectURL, $selectedMCC, $selectedMNC,
                                                      CompleteSelectedOperatorDiscoveryOptions $options,
                                                      IDiscoveryResponseCallback $callback, array $currentCookies);

    /**
     * A convenience version of {@link
     * IDiscovery#completeSelectedOperatorDiscovery()} where the clientId,
     * clientSecret and discoveryURL parameters are read from an IPreferences
     * implementation.
     *
     * @param IPreferences $preferences Instance of IPreferences that provides clientId,
     *        clientSecret and discoveryURL. (Required).
     * @param string $redirectURL The URL the operator selection functionality
     *        redirects to. If not specified http://localhost is assumed.
     *        (Optional).
     * @param string $selectedMCC The MCC of the selected operator. (Required).
     * @param string $selectedMNC The MNC of the selected operator. (Required).
     * @param CompleteSelectedOperatorDiscoveryOptions $specifiedOptions Optional parameters. (Optional).
     * @param IDiscoveryResponseCallback $callback Used to capture the response. (Required).
     * @param array $currentCookies the cookies to persist over rest services
     * @throws DiscoveryException
     */
    public function completeSelectedOperatorDiscoveryByPreferences(
        IPreferences $preferences, $redirectURL, $selectedMCC, $selectedMNC,
        CompleteSelectedOperatorDiscoveryOptions $specifiedOptions,
        IDiscoveryResponseCallback $callback, array $currentCookies);

    /**
     * Simple helper function which inspects the (JSON) returned from a
     * discovery SDK call to check if the operator selection UI should be
     * displayed.
     *
     * Returns true or false value. True is returned only if discoveryResult
     * contains the operator selection URL. False if discoveryResult is null or
     * contains expired discovery data.
     *
     * @param DiscoveryResponse $discoveryResult The discovery response to parse. (Required)
     * @return True if the discovery response contains an operator selection
     *         URL, false otherwise.
     * @throws \InvalidArgumentException Thrown if the discovery response is not
     *         recognised.
     */
    public function isOperatorSelectionRequired(
        DiscoveryResponse $discoveryResult);

    /**
     * Extract the operator selection URL from the discovery response.
     *
     * @param DiscoveryResponse $discoveryResult The discovery response to parse.
     * @return string The operator selection url or null.
     */
    public function extractOperatorSelectionURL(
        DiscoveryResponse $discoveryResult);

    /**
     * Returns true if the discovery response contains an error response
     *
     * @param DiscoveryResponse $discoveryResult The discovery response to check
     * @return bool True if the discovery response contains an error object.
     */
    public function isErrorResponse(DiscoveryResponse $discoveryResult);

    /**
     * Extract the error from the Discovery response if present.
     *
     * @param DiscoveryResponse $discoveryResult The discovery response to parse.
     * @return string The error response if any, null otherwise.
     */
    public function getErrorResponse(DiscoveryResponse $discoveryResult);

    /**
     * Simple function which retrieves (if available) from the discovery result
     * cache a discovery result which corresponds with the operator details
     * specified.
     *
     * @param string $mcc The operator mcc. (Required).
     * @param string $mnc The operator mnc. (Required).
     * @return mixed A cached entry if available, null otherwise.
     */
    public function getCachedDiscoveryResult($mcc, $mnc);

    /**
     * Simple function which clears any result from the discovery cache which
     * corresponds with the various options specified.
     *
     * @param CacheOptions $options Optional parameters, if not specified all entries are
     *     removed from the cache. (Optional).
     */
    public function clearDiscoveryCache(CacheOptions $options);
}
