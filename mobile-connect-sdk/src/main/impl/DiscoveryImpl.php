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

namespace MCSDK\impl;

use MCSDK\cache\DiscoveryCacheKey;
use MCSDK\cache\DiscoveryCacheValue;
use MCSDK\cache\IDiscoveryCache;
use MCSDK\discovery\CacheOptions;
use MCSDK\discovery\CompleteSelectedOperatorDiscoveryOptions;
use MCSDK\discovery\DiscoveryException;
use MCSDK\discovery\DiscoveryOptions;
use MCSDK\discovery\DiscoveryResponse;
use MCSDK\discovery\IDiscovery;
use MCSDK\discovery\IDiscoveryResponseCallback;
use MCSDK\discovery\IParsedDiscoveryRedirectCallback;
use MCSDK\discovery\IPreferences;
use MCSDK\discovery\ParsedDiscoveryRedirect;
use MCSDK\utils\Constants;
use MCSDK\utils\ErrorResponse;
use MCSDK\utils\HttpUtils;
use MCSDK\utils\JsonUtils;
use MCSDK\utils\RestClient;
use MCSDK\utils\RestException;
use MCSDK\utils\RestResponse;
use MCSDK\utils\TimeoutOptions;
use MCSDK\utils\URIBuilder;
use MCSDK\utils\URLUtils;
use MCSDK\utils\ValidationUtils;
use Zend\Http\Headers;

/**
 * An implementation of {@link IDiscovery}.
 *
 * An instance of this class is constructed using the {@link Factory} which allows an implementation of {@link IDiscoveryCache}
 * to be specified.
 */
class DiscoveryImpl implements IDiscovery
{

    private $_discoveryCache;
    private $_restClient;

    /**
     * DiscoveryImpl constructor.
     *
     * @param IDiscoveryCache $discoveryCache an instance of the local cache
     * @param RestClient $restClient an instance of the rest client used to interact with GSMA and operators
     */
    public function __construct(IDiscoveryCache $discoveryCache, RestClient $restClient)
    {
        $this->_discoveryCache = $discoveryCache;
        $this->_restClient = $restClient;
    }

    /**
     * Prior to automated operator discovery just verify that discovery preferences are valid
     *
     * @param IPreferences $preferences a configuration class to hold rest specific values
     * @param string $redirectURL the URL to redirect to after discovery
     * @param DiscoveryOptions $specifiedOptions options specific to discovery rest calls
     * @param IDiscoveryResponseCallback $callback a callback class to complete the discovery request
     * @param array $currentCookies current cookies to maintain a rest session
     */
    public function startAutomatedOperatorDiscoveryByPreferences(IPreferences $preferences, $redirectURL, DiscoveryOptions $specifiedOptions, IDiscoveryResponseCallback $callback, array $currentCookies)
    {
        ValidationUtils::validateParameter($preferences, "preferences");

        $this->startAutomatedOperatorDiscovery($preferences->getClientId(), $preferences->getClientSecret(), $preferences->getDiscoveryURL(), $redirectURL, $specifiedOptions, $callback, $currentCookies);
    }

    /**
     * Start the operator discovery process
     *
     * @param string $clientId the username for mobile connect discovery authorisation, developer credentials are specified on the GSMA developer portal
     * @param string $clientSecret the password for mobile connect discovery authorisation, developer credentials are specified on the GSMA developer portal
     * @param string $discoveryURI the URI to use for mobile connect discovery rest calls
     * @param string $redirectURL the URL to redirect to after an operator has been discovered
     * @param DiscoveryOptions $specifiedOptions a verbose list of options used to derive the options to be used
     * @param IDiscoveryResponseCallback $callback a callback class to complete the discovery request
     * @param array $currentCookies list of cookies used to maintain a rest based session
     * @throws \Exception|RestException
     */
    public function startAutomatedOperatorDiscovery($clientId, $clientSecret, $discoveryURI, $redirectURL, DiscoveryOptions $specifiedOptions, IDiscoveryResponseCallback $callback, array $currentCookies)
    {
        $this->validateDiscoveryParameters($clientId, $clientSecret, $discoveryURI, $redirectURL, $callback);
        $optionsToBeUsed = $this->getDiscoveryOptionsToBeUsed($specifiedOptions);

        $cacheKey = DiscoveryCacheKey::newWithDetails($optionsToBeUsed->getIdentifiedMCC(), $optionsToBeUsed->getIdentifiedMNC());

        $cachedValue = $this->getCachedValue($cacheKey);
        if (!is_null($cachedValue)) {
            $callback->completed($cachedValue);

            return;
        }

        try {
            $httpGet = $this->buildHttpGetParamsForOperatorDiscovery($discoveryURI, $redirectURL, $optionsToBeUsed);

            $context = $this->_restClient->getHttpClientContext($clientId, $clientSecret, $discoveryURI);

            $timeout = $optionsToBeUsed->getTimeout();

            //Extra header to be merged into headers (contains x-source-ip header)
            $extraHeaders = array(Constants::X_SOURCE_IP_HEADER_NAME => $optionsToBeUsed->getClientIP());

            $restResponse = $this->_restClient->callRestEndPoint($context, HttpUtils::getHTTPURI($httpGet), HttpUtils::getHTTPPath($httpGet), HttpUtils::getHTTPParamsAsArray($httpGet), $extraHeaders, $timeout, $currentCookies);

            $discoveryResponse = $this->buildDiscoveryResponse($restResponse->getStatusCode(), $restResponse->getHeaders(), JsonUtils::parseJson($restResponse->getResponse()));

            $this->addCachedValue($cacheKey, $discoveryResponse);

            $callback->completed($discoveryResponse);
        } catch (RestException $ex) {
            var_dump($ex);
            throw $this->newDiscoveryExceptionFromRestException("Call to Discovery End Point failed", $ex);
        } catch (\Exception $ex) {
            throw $this->newDiscoveryExceptionWithRestResponse("Calling Discovery service failed", null, $ex);
        }
    }

    /**
     * Once we have identified an operator we will need to validate that the preferences are correct prior to calling
     * the mobile connect network
     *
     * @param IPreferences $preferences the rest configuration details
     * @param string $redirectURL a URL to redirect to after we have the operators links
     * @param TimeoutOptions $specifiedOptions options specific to rest timeout configuration
     * @param IDiscoveryResponseCallback $callback a callback class to finalise the operator links request
     */
    public function getOperatorSelectionURLByPreferences(IPreferences $preferences, $redirectURL, TimeoutOptions $specifiedOptions, IDiscoveryResponseCallback $callback)
    {
        ValidationUtils::validateParameter($preferences, "preferences");

        $this->getOperatorSelectionURL($preferences->getClientId(), $preferences->getClientSecret(), $preferences->getDiscoveryURL(), $redirectURL, $specifiedOptions, $callback);
    }

    /**
     * Get a list of links specific to the discovered operator. These links allow us to communicate directly with an
     * operator and authorise the details specified at the start of the discovery process, for instance MSISDN.
     *
     * @param string $clientId the client id for REST auth
     * @param string $clientSecret the client secret for REST auth
     * @param string $discoveryURI the url to use for discovery REST requests
     * @param string $redirectURL the URL to redirect to after GSMA has completed our request
     * @param TimeoutOptions $specifiedOptions options for the REST timeout
     * @param IDiscoveryResponseCallback $callback the callback class to finalise this REST request
     * @throws \Exception
     */
    public function getOperatorSelectionURL($clientId, $clientSecret, $discoveryURI, $redirectURL, TimeoutOptions $specifiedOptions, IDiscoveryResponseCallback $callback)
    {
        $this->validateDiscoveryParameters($clientId, $clientSecret, $discoveryURI, $redirectURL, $callback);
        $optionsToBeUsed = $this->getOptionsToBeUsedWithTimeout($specifiedOptions);

        try {
            $httpGet = $this->buildHttpGetParamsForOperatorDiscovery($discoveryURI, $redirectURL, $optionsToBeUsed);

            $context = $this->_restClient->getHttpClientContext($clientId, $clientSecret, $discoveryURI);

            $timeout = $optionsToBeUsed->getTimeout();

            //Extra header to be merged into headers (contains x-source-ip header)s
            $extraHeaders = array(Constants::X_SOURCE_IP_HEADER_NAME => $optionsToBeUsed->getClientIP());

            $restResponse = $this->_restClient->callRestEndPoint($context, HttpUtils::getHTTPURI($httpGet), HttpUtils::getHTTPPath($httpGet),
                HttpUtils::getHTTPParamsAsArray($httpGet), $extraHeaders,  $timeout);


            $callback->completed($this->buildDiscoveryResponse($restResponse->getStatusCode(), $restResponse->getHeaders(), JsonUtils::parseJson($restResponse->getResponse())));
        } catch (RestException $ex) {
            throw $this->newDiscoveryExceptionFromRestException("Call to Discovery end point failed", $ex);
        } catch (\Exception $ex) {
            throw $this->newDiscoveryExceptionWithRestResponse("Calling Discovery service failed", null, $ex);
        }
    }

    /**
     * Uses the get params passed to our post discovery redirect URL to derive the mcc, mnc and subscriber id. These are
     * poked into the ParsedDiscoveryRedirect object which is handed over to the callback class to finalise the response
     * handling.
     *
     * @param string $redirectURL the url to redirect to
     * @param IParsedDiscoveryRedirectCallback $callback the callback class
     */
    public function parseDiscoveryRedirect($redirectURL, IParsedDiscoveryRedirectCallback $callback)
    {
        ValidationUtils::validateParameter($redirectURL, "redirectURL");
        ValidationUtils::validateParameter($callback, "callback");

        $parameters = URLUtils::getGetParamsAsArray($redirectURL);
        $mcc_mnc = HttpUtils::getParameterValue($parameters, Constants::MCC_MNC_PARAMETER_NAME);
        $subscriber_id = HttpUtils::getParameterValue($parameters, Constants::SUBSCRIBER_ID_PARAMETER_NAME);

        $mcc = null;
        $mnc = null;
        if (!is_null($mcc_mnc)) {
            $parts = explode('_', $mcc_mnc);
            if (count($parts) == 2) {
                $mcc = $parts[0];
                $mnc = $parts[1];
            }
        }

        $callback->completed(new ParsedDiscoveryRedirect($mcc, $mnc, $subscriber_id));
    }

    /**
     * Validate the preferences prior to completing the operator discovery response handling.
     *
     * @param IPreferences $preferences the rest configuration details
     * @param string $redirectURL the url redirected to on completion of the operator selection process
     * @param string $selectedMCC the selected MCC posted from the operator discovery form
     * @param string $selectedMNC the selected MNC posted from the operator discovery form
     * @param CompleteSelectedOperatorDiscoveryOptions $specifiedOptions options specified for the previous rest call
     * @param IDiscoveryResponseCallback $callback the discovery response callback handler
     * @param array $currentCookies list of cookies used to maintain a rest based session
     */
    public function completeSelectedOperatorDiscoveryByPreferences(IPreferences $preferences, $redirectURL, $selectedMCC, $selectedMNC, CompleteSelectedOperatorDiscoveryOptions $specifiedOptions, IDiscoveryResponseCallback $callback, array $currentCookies)
    {
        ValidationUtils::validateParameter($preferences, "preferences");

        $this->completeSelectedOperatorDiscovery($preferences->getClientId(), $preferences->getClientSecret(), $preferences->getDiscoveryURL(), $redirectURL, $selectedMCC, $selectedMNC, $specifiedOptions, $callback, $currentCookies);
    }

    /**
     * Handles the request to select an operator manually in the event the MSISDN could not be found or was invalid.
     *
     * @param string $clientId the username for rest authentication
     * @param string $clientSecret the password for rest authentication
     * @param string $discoveryURL the url used to select an operator manually for discovery
     * @param string $redirectURI the url to redirect to after the operator discovery request is successful
     * @param string $selectedMCC the MCC of our operator
     * @param string $selectedMNC the MNC of our operator
     * @param CompleteSelectedOperatorDiscoveryOptions $specifiedOptions options specific to this request
     * @param IDiscoveryResponseCallback $callback the callback class to complete manual operator discovery requests
     * @param array $currentCookies list of cookies used to maintain a rest based session
     * @throws RestException
     * @throws \Exception
     */
    public function completeSelectedOperatorDiscovery($clientId, $clientSecret,
                                                      $discoveryURL, $redirectURI, $selectedMCC, $selectedMNC,
                                                      CompleteSelectedOperatorDiscoveryOptions $specifiedOptions,
                                                      IDiscoveryResponseCallback $callback, array $currentCookies)
    {
        if (is_null($redirectURI)) {
            $redirectURI = Constants::DEFAULT_REDIRECT_URL;
        }
        $this->validateDiscoveryParametersMCCMNC($clientId, $clientSecret, $redirectURI, $selectedMCC, $selectedMNC, $callback);

        $optionsToBeUsed = $this->getSelectedOperatorDiscoveryOptions($specifiedOptions);

        $cacheKey = DiscoveryCacheKey::newWithDetails($selectedMCC, $selectedMNC);
        $cachedValue = $this->getCachedValue($cacheKey);
        if (!is_null($cachedValue)) {
            $callback->completed($cachedValue);

            return;
        }

        try {
            $discoveryURI = $discoveryURL;
            $httpGet = $this->buildHttpGetParamsForCompleteSelectedOperatorDiscovery($discoveryURI, $redirectURI, $selectedMCC, $selectedMNC);

            $context = $this->_restClient->getHttpClientContext($clientId, $clientSecret, $discoveryURI);

            $timeout = $specifiedOptions->getTimeout();

            //Extra header to be merged into headers (contains x-source-ip header)
            $extraHeaders = array(Constants::X_SOURCE_IP_HEADER_NAME => $optionsToBeUsed->getClientIP());

            $restResponse = $this->_restClient->callRestEndPoint($context, HttpUtils::getHTTPURI($httpGet), HttpUtils::getHTTPPath($httpGet), HttpUtils::getHTTPParamsAsArray($httpGet), $extraHeaders, $timeout, $currentCookies);

            $callback->completed($this->buildDiscoveryResponse($restResponse->getStatusCode(), $restResponse->getHeaders(), JsonUtils::parseJson($restResponse->getResponse())));
        } catch (RestException $ex) {
            throw $this->newDiscoveryExceptionFromRestException("Call to Discovery end point failed", $ex);
        } catch (\Exception $ex) {
            throw $this->newDiscoveryExceptionWithRestResponse("Calling Discovery service failed", null, $ex);
        }
    }

    /**
     * Get the url from the operator discovery response
     *
     * @param DiscoveryResponse $discoveryResult response form the discovery requests
     * @return string|null url extracted from discovery responses
     */
    public function extractOperatorSelectionURL(DiscoveryResponse $discoveryResult)
    {
        if (!$this->isValidDiscoveryResponse($discoveryResult)) {

            return null;
        }

        return JsonUtils::extractUrl($discoveryResult->getResponseData(), Constants::OPERATOR_SELECTION_REL);
    }

    /**
     * Check that we can select an operator with our discovery response
     *
     * @param DiscoveryResponse $discoveryResult result from discovery REST call
     * @return bool true if has an operator URL from the discovery process
     */
    public function isOperatorSelectionRequired(DiscoveryResponse $discoveryResult)
    {
        $this->validateDiscoveryResponse($discoveryResult);

        return !is_null($this->extractOperatorSelectionURL($discoveryResult));
    }

    /**
     * Determine if there is an error in the discovery response
     *
     * @param DiscoveryResponse $discoveryResult the response object from the discovery process
     * @return bool true if the error response has a value
     */
    public function isErrorResponse(DiscoveryResponse $discoveryResult)
    {

        return $this->getErrorResponse($discoveryResult) != null;
    }

    /**
     * Get the response body from a discovery error response
     *
     * @param DiscoveryResponse $discoveryResult the response from the discovery process
     * @return ErrorResponse|null if error response return ir
     */
    public function getErrorResponse(DiscoveryResponse $discoveryResult)
    {
        if (is_null($discoveryResult) || is_null($discoveryResult->getResponseData())) {

            return null;
        }

        return JsonUtils::getErrorResponse($discoveryResult->getResponseData());
    }

    /**
     * Get the cached discovery result
     *
     * @param string $mcc the mobile country code
     * @param string $mnc the mobile network code
     * @return DiscoveryCacheValue the cached value for the mcc and mnc if available
     */
    public function getCachedDiscoveryResult($mcc, $mnc)
    {
        ValidationUtils::validateParameter($mcc, "mcc");
        ValidationUtils::validateParameter($mnc, "mnc");

        return $this->getCachedValue(DiscoveryCacheKey::newWithDetails($mcc, $mnc));
    }

    /**
     * Clear the discovery cache
     *
     * @param CacheOptions $options used to create a fresh cache with mcc and mnc
     */
    public function clearDiscoveryCache(CacheOptions $options)
    {
        if (is_null($this->_discoveryCache)) {

            return;
        }

        if (is_null($options)) {
            $this->_discoveryCache->clear();

            return;
        }

        $key = DiscoveryCacheKey::newWithDetails($options->getMCC(), $options->getMNC());
        if (is_null($key)) {

            return;
        }
        $this->_discoveryCache->remove($key);
    }

    /**
     * Check the discovery parameters are valid prior to starting discovery
     *
     * @param string $clientId the client id used to auth with GSMA' api
     * @param string $clientSecret the client secret used to auth with GSMA' api
     * @param string $discoveryURL the url for discovery
     * @param string $redirectURL the url for GSMA to redirect to prior to discovery
     * @param IDiscoveryResponseCallback $callback the callback class to handle the response from discovery
     */
    private function validateDiscoveryParameters($clientId, $clientSecret, $discoveryURL, $redirectURL, IDiscoveryResponseCallback $callback)
    {
        ValidationUtils::validateParameter($clientId, "clientId");
        ValidationUtils::validateParameter($clientSecret, "clientSecret");
        ValidationUtils::validateParameter($discoveryURL, "discoveryURL");
        ValidationUtils::validateParameter($redirectURL, "redirectURL");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Check the discovery parameters are valid prior to starting discovery using selected mcc and mnc values
     *
     * @param string $clientId the client id used to auth with GSMA' api
     * @param string $clientSecret the client secret used to auth with GSMA' api
     * @param string $discoveryURL the url for discovery
     * @param string $selectedMCC the selected mobile country code
     * @param string $selectedMNC the selected mobile network code
     * @param IDiscoveryResponseCallback $callback the callback class to handle the response from discovery
     */
    private function validateDiscoveryParametersMCCMNC($clientId, $clientSecret, $discoveryURL, $selectedMCC, $selectedMNC, IDiscoveryResponseCallback $callback)
    {
        ValidationUtils::validateParameter($clientId, "clientId");
        ValidationUtils::validateParameter($clientSecret, "clientSecret");
        ValidationUtils::validateParameter($discoveryURL, "discoveryURL");
        ValidationUtils::validateParameter($selectedMCC, "selectedMCC");
        ValidationUtils::validateParameter($selectedMNC, "selectedMNC");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Validate that the discovery response is correct
     *
     * @param DiscoveryResponse $discoveryResult the response to be tested
     */
    private function validateDiscoveryResponse(DiscoveryResponse $discoveryResult)
    {
        ValidationUtils::validateParameter($discoveryResult, "discoveryResult");
        if (!$this->isValidDiscoveryResponse($discoveryResult)) {
            throw new \InvalidArgumentException("Not a valid discoveryResult");
        }
    }

    /**
     * Does the Discovery Response hold valid data?
     *
     * @param DiscoveryResponse $discoveryResult The discovery response to examine.
     * @return bool True is there is data and it is from the cache or the response code indicates it is valid.
     */
    private function isValidDiscoveryResponse(DiscoveryResponse $discoveryResult)
    {
        if (is_null($discoveryResult) || is_null($discoveryResult->getResponseData())) {

            return false;
        }

        if ($discoveryResult->isCached()) {

            return true;
        }

        $responseCode = $discoveryResult->getResponseCode();

        return ($responseCode == Constants::OPERATOR_IDENTIFIED_RESPONSE || $responseCode == Constants::OPERATOR_NOT_IDENTIFIED_RESPONSE);
    }

    /**
     * Return the options to be used for the getOperatorSelectionURL call.
     *
     * Use caller provided values if passed, use defaults otherwise.
     *
     * @param TimeoutOptions $originalOptions The caller specified options if any.
     * @return DiscoveryOptions The options to be used.
     */
    private function getOptionsToBeUsedWithTimeout(TimeoutOptions $originalOptions)
    {
        $optionsToBeUsed = new DiscoveryOptions();
        $optionsToBeUsed->setManuallySelect(true);
        $optionsToBeUsed->setIdentifiedMCC(null);
        $optionsToBeUsed->setIdentifiedMNC(null);
        $optionsToBeUsed->setUsingMobileData(false);
        $optionsToBeUsed->setCookiesEnabled(true);
        $optionsToBeUsed->setLocalClientIP(null);

        $tmpOptions = $originalOptions;
        if (is_null($tmpOptions)) {
            $tmpOptions = new TimeoutOptions();
        }
        $optionsToBeUsed->setTimeout($tmpOptions->getTimeout());

        return $optionsToBeUsed;
    }

    /**
     * Return the options to be used for the CompleteSelectedOperatorDiscovery call.
     *
     * Use caller provided values if passed, use defaults otherwise.
     *
     * @param DiscoveryOptions $originalOptions The caller specified options if any.
     * @return DiscoveryOptions The options to be used.
     */
    private function getDiscoveryOptionsToBeUsed(DiscoveryOptions $originalOptions)
    {
        $tmpOptions = $originalOptions;
        if (is_null($tmpOptions)) {
            $tmpOptions = new CompleteSelectedOperatorDiscoveryOptions();
        }

        return $tmpOptions;
    }

    /**
     * Return the options to be used for the completeSelectedOperatorDiscovery call.
     *
     * Use caller provided values if passed, use defaults otherwise.
     *
     * @param CompleteSelectedOperatorDiscoveryOptions $originalOptions The caller specified options if any.
     * @return DiscoveryOptions The options to be used.
     */
    private function getSelectedOperatorDiscoveryOptions(CompleteSelectedOperatorDiscoveryOptions $originalOptions)
    {
        $optionsToBeUsed = new DiscoveryOptions();
        $optionsToBeUsed->setManuallySelect(true);
        $optionsToBeUsed->setIdentifiedMCC(null);
        $optionsToBeUsed->setIdentifiedMNC(null);
        $optionsToBeUsed->setUsingMobileData(false);
        $optionsToBeUsed->setCookiesEnabled(true);
        $optionsToBeUsed->setLocalClientIP(null);

        $tmpOptions = $originalOptions;
        if (is_null($tmpOptions)) {
            $tmpOptions = new TimeoutOptions();
        }
        $optionsToBeUsed->setTimeout($tmpOptions->getTimeout());

        return $optionsToBeUsed;
    }

    /**
     * Return a value from the cache.
     *
     * @param DiscoveryCacheKey $key Identifies the value to return.
     * @return DiscoveryCacheValue The cached value if available, null otherwise.
     */
    private function getCachedValue(DiscoveryCacheKey $key = null)
    {
        if (is_null($this->_discoveryCache)) {
            return null;
        }
        if (is_null($key)) {
            return null;
        }

        $value = $this->_discoveryCache->get($key);
        if (is_null($value)) {
            return null;
        }

        return $this->buildDiscoveryResponseFromCache($value);
    }

    /**
     * Add a value to the cache.
     *
     * Only operator identified responses are cached.
     *
     * @param DiscoveryCacheKey $key The cache key.
     * @param DiscoveryResponse $value The value to cache.
     */
    public function addCachedValue(DiscoveryCacheKey $key = null, DiscoveryResponse $value = null)
    {
        if (is_null($this->_discoveryCache)) {
            return;
        }
        if (is_null($key) || is_null($value)) {
            return;
        }
        if ($value->getResponseCode() != Constants::OPERATOR_IDENTIFIED_RESPONSE) {
            return;
        }
        if (is_null($value->getTtl()) || is_null($value->getResponseData())) {
            return;
        }
        $this->_discoveryCache->add($key, new DiscoveryCacheValue($value->getTtl(), $value->getResponseData()));
    }

    /**
     * Build a HttpGet for the Discovery Service.
     *
     * @param string $discoveryURI URI of the discovery service.
     * @param string $redirectURL The redirect URL to pass as a parameter.
     * @param CompleteSelectedOperatorDiscoveryOptions|DiscoveryOptions $options Optional parameters to the get.
     * @return string The uri formed using the domain, protocol and parameters
     */
    private function buildHttpGetParamsForOperatorDiscovery($discoveryURI, $redirectURL, $options)
    {
        $uri = new URIBuilder($discoveryURI);
        $uri->addParameter(Constants::MANUALLY_SELECT_PARAMETER_NAME, $options->isManuallySelect());
        $uri->addParameter(Constants::IDENTIFIED_MCC_PARAMETER_NAME, $options->getIdentifiedMCC());
        $uri->addParameter(Constants::IDENTIFIED_MNC_PARAMETER_NAME, $options->getIdentifiedMNC());
        $uri->addParameter(Constants::USING_MOBILE_DATA_PARAMETER_NAME, $options->isUsingMobileData());
        $uri->addParameter(Constants::LOCAL_CLIENT_IP_PARAMETER_NAME, $options->getLocalClientIP());
        $uri->addParameter(Constants::REDIRECT_URL_PARAMETER_NAME, $redirectURL);

        return $uri->build();
    }

    /**
     * Creates a list of parameters for the selected operator discovery requests
     *
     * @param string $discoveryURI the URI to use for selected operator discovery
     * @param string $redirectURL the redirect to use by GSMA after the request is complete
     * @param string $selectedMCC the selected mobile country code
     * @param string $selectedMNC the selected mobile network code
     * @return string the uri to be used for selected operator discovery
     */
    private function buildHttpGetParamsForCompleteSelectedOperatorDiscovery(
        $discoveryURI, $redirectURL, $selectedMCC, $selectedMNC)
    {
        $uri = new URIBuilder($discoveryURI);
        $uri->addParameter(Constants::REDIRECT_URL_PARAMETER_NAME, $redirectURL);
        $uri->addParameter(Constants::SELECTED_MCC_PARAMETER_NAME, $selectedMCC);
        $uri->addParameter(Constants::SELECTED_MNC_PARAMETER_NAME, $selectedMNC);

        return $uri->build();

    }

    /**
     * Converts a rest exceptions into a discovery exception with a custom message
     *
     * @param string $message the message to be used for this exception
     * @param RestException $restException the rest exception to be converted
     * @return DiscoveryException the new exception
     */
    private function newDiscoveryExceptionFromRestException($message, RestException $restException)
    {

        return new DiscoveryException($message, $restException->getUri(), $restException->getStatusCode(), $restException->getHeaders(), $restException->getContents(), $restException);
    }

    /**
     * Create a discovery specific exception
     *
     * @param string $message the exception message
     * @param RestResponse $restResponse the response returned by the Zend_Rest_client
     * @param \Exception $ex a given exception
     * @return DiscoveryException the exception to be thrown
     */
    private function newDiscoveryExceptionWithRestResponse($message, RestResponse $restResponse = null, \Exception $ex)
    {
        if (is_null($restResponse)) {
            return new DiscoveryException($message, $ex);
        } else {
            return new DiscoveryException($message, $restResponse->getUri(), $restResponse->getStatusCode(), $restResponse->getHeaders(), $restResponse->getResponse(), $ex);
        }
    }

    /**
     * On successful start discovery we need to store the response parts in an object for use throughout the application
     *
     * @param int $responseCode the http response code
     * @param \Zend\Http\Headers $headers the list of headers in the response
     * @param \stdClass $jsonDoc the object parsed from json
     * @return DiscoveryResponse the constructed discovery response object
     */
    private function buildDiscoveryResponse($responseCode, Headers $headers, \stdClass $jsonDoc)
    {
        $ttl = $this->determineTtl(JsonUtils::getDiscoveryResponseTtl($jsonDoc));

        return new DiscoveryResponse(false, $ttl, $responseCode, $headers, $jsonDoc);
    }

    /**
     * Extract the discovery response from a given discovery cache value
     *
     * @param DiscoveryCacheValue $value the value to be used to create a response simulating a discovery request
     * @return DiscoveryResponse a fully formed discovery response as if the discovery service has been called
     */
    private function buildDiscoveryResponseFromCache(DiscoveryCacheValue $value)
    {
        return new DiscoveryResponse(true, $value->getTtl(), 0, new \Zend\Http\Headers(), $value->getValue());
    }

    /**
     * Determine the time-to-live for a discovery response.
     *
     * Ensure the ttl is between a minimum and maximum value.
     *
     * @param int $ttlTime The ttl value from the discovery result.
     * @return \DateTime The ttl to use.
     */
    public function determineTtl($ttlTime)
    {

        $dateTimeInstance = new \DateTime("now", new \DateTimeZone("Europe/London"));

        $min = $dateTimeInstance->setTimestamp(microtime(true) + Constants::MINIMUM_TTL_MS);
        $max = $dateTimeInstance->setTimestamp(microtime(true) + Constants::MAXIMUM_TTL_MS);

        if (is_null($ttlTime)) {
            return $dateTimeInstance->setTimestamp($min->getTimestamp());
        }

        $currentTtl = $dateTimeInstance->setTimestamp($ttlTime);

        if ($currentTtl < $min) {
            return $dateTimeInstance->setTimestamp($min->getTimestamp());
        }

        if ($currentTtl > $max) {
            return $dateTimeInstance->setTimestamp($max->getTimestamp());
        }

        return $currentTtl;
    }

}
