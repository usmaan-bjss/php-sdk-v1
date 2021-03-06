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

use MCSDK\discovery\DiscoveryResponse;
use MCSDK\oidc\AuthenticationOptions;
use MCSDK\oidc\DiscoveryResponseExpiredException;
use MCSDK\oidc\IOIDC;
use MCSDK\oidc\IParseAuthenticationResponseCallback;
use MCSDK\oidc\IParseIDTokenCallback;
use MCSDK\oidc\IRequestTokenCallback;
use MCSDK\oidc\IStartAuthenticationCallback;
use MCSDK\oidc\OIDCException;
use MCSDK\oidc\ParsedAuthorizationResponse;
use MCSDK\oidc\StartAuthenticationResponse;
use MCSDK\oidc\TokenOptions;
use MCSDK\utils\Constants;
use MCSDK\utils\HttpUtils;
use MCSDK\utils\JsonUtils;
use MCSDK\utils\RestClient;
use MCSDK\utils\RestException;
use MCSDK\utils\RestResponse;
use MCSDK\utils\URIBuilder;
use MCSDK\utils\ValidationUtils;

/**
 * An implementation of {@link IOIDC}.
 *
 * An instance of this class is constructed using the {@link Factory}.
 */
class OIDCImpl implements IOIDC
{

    private $restClient;

    /**
     * OIDCImpl constructor.
     *
     * @param RestClient $restClient the rest client to use when interacting with GSMA and operators
     */
    public function __construct(RestClient $restClient)
    {
        $this->restClient = $restClient;
    }

    /**
     * Start the authentication comms via rest with the operator
     *
     * @param DiscoveryResponse $discoveryResult the result from start discovery request
     * @param string $redirectURI
     * @param string $state
     * @param string $nonce
     * @param string $scope
     * @param int $maxAge
     * @param int $acrValues
     * @param string $encryptedMSISDN
     * @param AuthenticationOptions $specifiedOptions
     * @param IStartAuthenticationCallback $callback
     * @throws DiscoveryResponseExpiredException
     * @throws OIDCException
     */
    public function startAuthentication(DiscoveryResponse $discoveryResult, $redirectURI, $state, $nonce, $scope, $maxAge, $acrValues, $encryptedMSISDN, AuthenticationOptions $specifiedOptions, IStartAuthenticationCallback $callback)
    {
        $this->validateAuthenticationParameters($discoveryResult, $redirectURI, $nonce, $callback);
        $scope = $this->getScope($scope);
        $maxAge = $this->getMaxAge($maxAge);
        $acrValues = $this->getAcrValues($acrValues);

        $optionsToBeUsed = $this->getAuthenticationOptionsToBeUsed($specifiedOptions);

        $parsedOperatorIdentifiedDiscoveryResult = JsonUtils::parseOperatorIdentifiedDiscoveryResult($discoveryResult->getResponseData());
        if (null == $parsedOperatorIdentifiedDiscoveryResult) {
            throw new OIDCException("Not a valid discovery result.");
        }

        $authorizationHref = $parsedOperatorIdentifiedDiscoveryResult->getAuthorizationHref();
        if (is_null($authorizationHref)) {
            throw new OIDCException("No authorization href");
        }

        $builder = $this->getUriBuilder($authorizationHref);

        $builder->addParameter(Constants::CLIENT_ID_PARAMETER_NAME, $parsedOperatorIdentifiedDiscoveryResult->getClientId());
        $builder->addParameter(Constants::RESPONSE_TYPE_PARAMETER_NAME, Constants::RESPONSE_TYPE_PARAMETER_VALUE);
        $builder->addParameter(Constants::SCOPE_PARAMETER_NAME, $scope);
        $builder->addParameter(Constants::REDIRECT_URI_PARAMETER_NAME, $redirectURI);
        $builder->addParameter(Constants::ACR_VALUES_PARAMETER_NAME, $acrValues);
        if (!is_null($state)) {
            $builder->addParameter(Constants::STATE_PARAMETER_NAME, $state);
        }
        $builder->addParameter(Constants::NONCE_PARAMETER_NAME, $nonce);
        $builder->addParameter(Constants::DISPLAY_PARAMETER_NAME, $optionsToBeUsed->getDisplay());
        if (!is_null($optionsToBeUsed->getPrompt())) {
            $builder->addParameter(Constants::PROMPT_PARAMETER_NAME, $optionsToBeUsed->getPrompt());
        }
        $builder->addParameter(Constants::MAX_AGE_PARAMETER_NAME, (string)$maxAge);
        if (!is_null($optionsToBeUsed->getUiLocales())) {
            $builder->addParameter(Constants::UI_LOCALES_PARAMETER_NAME, $optionsToBeUsed->getUiLocales());
        }
        if (!is_null($optionsToBeUsed->getClaimsLocales())) {
            $builder->addParameter(Constants::CLAIMS_LOCALES_PARAMETER_NAME, $optionsToBeUsed->getClaimsLocales());
        }
        if (!is_null($optionsToBeUsed->getIdTokenHint())) {
            $builder->addParameter(Constants::ID_TOKEN_HINT_PARAMETER_NAME, $optionsToBeUsed->getIdTokenHint());
        }
        if (!is_null($optionsToBeUsed->getLoginHint())) {
            $builder->addParameter(Constants::LOGIN_HINT_PARAMETER_NAME, $optionsToBeUsed->getLoginHint());
        } else if (!is_null($encryptedMSISDN)) {
            $builder->addParameter(Constants::LOGIN_HINT_PARAMETER_NAME, Constants::ENCRYPTED_MSISDN_PREFIX . $encryptedMSISDN);
        }
        if (!is_null($optionsToBeUsed->getDtbs())) {
            $builder->addParameter(Constants::DTBS_PARAMETER_NAME, $optionsToBeUsed->getDtbs());
        }

        $authenticationResponse = new StartAuthenticationResponse();
        $authenticationResponse->setUrl($this->buildUrl($builder));
        $authenticationResponse->setScreenMode($optionsToBeUsed->getScreenMode());

        $callback->complete($authenticationResponse);
    }

    /**
     * See {@link IOIDC#parseAuthenticationResponse(String, IParseAuthenticationResponseCallback)}
     *
     * @param string $redirectURL the url to be redirected to once GSMA complete out request
     * @param IParseAuthenticationResponseCallback $callback callback class to finalise this request
     * @throws OIDCException
     */
    public function parseAuthenticationResponse($redirectURL, IParseAuthenticationResponseCallback $callback)
    {
        $this->validateParseAuthenticationResponseParameters($redirectURL, $callback);

        $nameValuePairs = $this->getParameters($redirectURL);

        $parsedAuthorizationResponse = new ParsedAuthorizationResponse();
        $parsedAuthorizationResponse->set_error(HttpUtils::getParameterValue($nameValuePairs, Constants::ERROR_NAME));
        $parsedAuthorizationResponse->set_error_description(HttpUtils::getParameterValue($nameValuePairs, Constants::ERROR_DESCRIPTION_NAME));
        $parsedAuthorizationResponse->set_error_uri(HttpUtils::getParameterValue($nameValuePairs, Constants::ERROR_URI_NAME));
        $parsedAuthorizationResponse->set_state(HttpUtils::getParameterValue($nameValuePairs, Constants::STATE_PARAMETER_NAME));
        $parsedAuthorizationResponse->set_code(HttpUtils::getParameterValue($nameValuePairs, Constants::CODE_PARAMETER_NAME));

        $callback->complete($parsedAuthorizationResponse);
    }

    /**
     * See {@link IOIDC#requestToken(DiscoveryResponse, String, String, TokenOptions, IRequestTokenCallback)}
     *
     * @param DiscoveryResponse $discoveryResult
     * @param string $redirectURI
     * @param int $code
     * @param TokenOptions $specifiedOptions
     * @param IRequestTokenCallback $callback
     * @throws DiscoveryResponseExpiredException
     * @throws OIDCException
     * @throws \Exception
     */
    public function requestToken(DiscoveryResponse $discoveryResult, $redirectURI, $code, TokenOptions $specifiedOptions, IRequestTokenCallback $callback)
    {
        $this->validateTokenParameters($discoveryResult, $redirectURI, $code, $callback);

        $parsedOperatorIdentifiedDiscoveryResult = JsonUtils::parseOperatorIdentifiedDiscoveryResult($discoveryResult->getResponseData());
        if (is_null($parsedOperatorIdentifiedDiscoveryResult)) {
            throw new OIDCException("Not a valid discovery result.");
        }

        $tokenURL = $parsedOperatorIdentifiedDiscoveryResult->getTokenHref();
        if (is_null($tokenURL)) {
            throw new OIDCException("No token href");
        }
        $clientId = $parsedOperatorIdentifiedDiscoveryResult->getClientId();
        $clientSecret = $parsedOperatorIdentifiedDiscoveryResult->getClientSecret();

        $optionsToUse = $this->getTokenOptionsToBeUsed($specifiedOptions);

        try {
            $uriBuilder = new URIBuilder($tokenURL);
            $tokenURI = $uriBuilder->build();

            $uri = $this->buildHttpPostParamsForAccessToken($tokenURI, $redirectURI, $code);

            $context = $this->restClient->getHttpClientContext($clientId, $clientSecret, $tokenURI);

            $restResponse = $this->restClient->callRestEndPoint($context, HttpUtils::getHTTPURI($uri), HttpUtils::getHTTPPath($uri), HttpUtils::getHTTPParamsAsArray($uri), array(), $optionsToUse->getTimeout());

            $requestTokenResponse = JsonUtils::parseRequestTokenResponse(new \DateTime("now", new \DateTimeZone("Europe/London")), $restResponse->getResponse());
            $requestTokenResponse->setResponseCode($restResponse->getStatusCode());
            $requestTokenResponse->setHeaders($restResponse->getHeaders());

            $callback->complete($requestTokenResponse);
        } catch (RestException $ex) {
            throw $this->newOIDCExceptionFromRestException("Call to Token end point failed", $ex);
        } catch (\Exception $ex) {
            throw $this->newOIDCExceptionWithRestResponse("Calling Discovery service failed", null, $ex);
        }
    }

    /**
     * See {@link IOIDC#parseIDToken(DiscoveryResponse, String, TokenOptions, IParseIDTokenCallback)}.
     *
     * @param DiscoveryResponse $discoveryResult result from the discovery REST request
     * @param string $id_tokenStr the id token string from our REST request response
     * @param TokenOptions $options Token options class
     * @param IParseIDTokenCallback $callback callback class to handle the parsing of token information
     * @throws DiscoveryResponseExpiredException
     * @throws OIDCException
     */
    public function parseIDToken(DiscoveryResponse $discoveryResult, $id_tokenStr, TokenOptions $options, IParseIDTokenCallback $callback)
    {
        $this->validateParseIdTokenParameters($discoveryResult, $id_tokenStr, $callback);
        try {
            $parsedIdToken = JsonUtils::createParsedIdToken($id_tokenStr);
            $callback->complete($parsedIdToken);
        } catch (\Exception $ex) {
            throw new OIDCException("Not an id_token", $ex);
        }
    }

    /**
     * Validate the authentication parameters
     *
     * @param DiscoveryResponse $discoveryResult the result cached from discovery
     * @param string $redirectURI the uri to redirect to after authorisation has been started
     * @param string $nonce the nonce code for rest calls
     * @param IStartAuthenticationCallback $callback the callback class to complete this request
     * @throws DiscoveryResponseExpiredException
     */
    private function validateAuthenticationParameters(DiscoveryResponse $discoveryResult, $redirectURI, $nonce, IStartAuthenticationCallback $callback)
    {
        ValidationUtils::validateParameter($discoveryResult, "discoveryResult");
        if ($discoveryResult->hasExpired()) {
            throw new DiscoveryResponseExpiredException("discoveryResult has expired");
        }
        ValidationUtils::validateParameter($redirectURI, "redirectURI");
        ValidationUtils::validateParameter($nonce, "nonce");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Validate the token parameters
     *
     * @param DiscoveryResponse $discoveryResult the cached discovery result
     * @param string $redirectURI the uri to redirect to once this process is complete
     * @param string $code
     * @param IRequestTokenCallback $callback the class to complete the request
     * @throws DiscoveryResponseExpiredException
     */
    private function validateTokenParameters(DiscoveryResponse $discoveryResult, $redirectURI, $code, IRequestTokenCallback $callback)
    {
        ValidationUtils::validateParameter($discoveryResult, "discoveryResult");
        if ($discoveryResult->hasExpired()) {
            throw new DiscoveryResponseExpiredException("discoveryResult has expired");
        }
        ValidationUtils::validateParameter($redirectURI, "redirectURI");
        ValidationUtils::validateParameter($code, "code");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Validate that the token id params can be parsed
     *
     * @param DiscoveryResponse $discoveryResult the result from the authorisation request
     * @param string $idTokenStr the id token as a string prior to parsing
     * @param IParseIDTokenCallback $callback the callback class to complete the authorisation request
     * @throws DiscoveryResponseExpiredException
     */
    private function validateParseIdTokenParameters(DiscoveryResponse $discoveryResult, $idTokenStr, IParseIDTokenCallback $callback)
    {
        ValidationUtils::validateParameter($discoveryResult, "discoveryResult");
        if ($discoveryResult->hasExpired()) {
            throw new DiscoveryResponseExpiredException("discoveryResult has expired");
        }
        ValidationUtils::validateParameter($idTokenStr, "id_token");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Check the response from the authorisation request is correct
     *
     * @param string $redirectURL the url to redirect to in the local application
     * @param IParseAuthenticationResponseCallback $callback the callback class used to complete authorisation
     */
    private function validateParseAuthenticationResponseParameters($redirectURL, IParseAuthenticationResponseCallback $callback)
    {
        ValidationUtils::validateParameter($redirectURL, "redirectURL");
        ValidationUtils::validateParameter($callback, "callback");
    }

    /**
     * Get the options to be used for the startAuthentication call.
     *
     * Use provided values or defaults.
     *
     * @param AuthenticationOptions $specifiedOptions Provided value, may be null.
     * @return AuthenticationOptions Options to be used.
     */
    private function getAuthenticationOptionsToBeUsed($specifiedOptions)
    {
        $optionsToBeUsed = $specifiedOptions;
        if (is_null($optionsToBeUsed)) {
            $optionsToBeUsed = new AuthenticationOptions();
        }

        return $optionsToBeUsed;
    }

    /**
     * Get the options to be used for the startAuthentication call.
     *
     * Use provided values or defaults.
     *
     * @param TokenOptions $specifiedOptions Provided value, may be null.
     * @return TokenOptions Options to be used.
     */
    private function getTokenOptionsToBeUsed($specifiedOptions)
    {
        $optionsToBeUsed = $specifiedOptions;
        if (is_null($optionsToBeUsed)) {
            $optionsToBeUsed = new TokenOptions();
        }

        return $optionsToBeUsed;
    }

    /**
     * Utility method to create a URIBuilder or throw a OIDCException.
     *
     * @param string $authorizationHref The base URI
     * @return URIBuilder A URIBuilder
     * @throws OIDCException Thrown if the authorizationHref is invalid.
     */
    private function getUriBuilder($authorizationHref)
    {
        try {
            return new URIBuilder($authorizationHref);
        } catch (\Exception $ex) {
            throw new OIDCException("Invalid URI", $ex);
        }
    }

    /**
     * Utility method to get the string version of the URI or thrown an
     * OIDCException.
     *
     * @param URIBuilder $builder The URIBuilder.
     * @return string The string uri.
     * @throws OIDCException Thrown if the uri is invalid.
     */
    private function buildUrl(URIBuilder $builder)
    {
        try {
            return urldecode($builder->build());
        } catch (\Exception $ex) {
            throw new OIDCException("Invalid URI", $ex);
        }
    }

    /**
     * Return the acr values to be used.
     *
     * Either provided values or default value.
     *
     * @param int $acrValues To provided acr values.
     * @return string The acr values to use.
     */
    private function getAcrValues($acrValues = null)
    {
        if (is_null($acrValues)) {
            $acrValues = Constants::DEFAULT_ACRVALUES_VALUE;
        }

        return $acrValues;
    }

    /**
     * Return the max age value to be used.
     *
     * Either the provided value or a default value.
     *
     * @param int $maxAge Provided max age value.
     * @return int The max age value to be used.
     */
    private function getMaxAge($maxAge = null)
    {
        if (is_null($maxAge)) {
            $maxAge = Constants::DEFAULT_MAXAGE_VALUE;
        }
        return $maxAge;
    }

    /**
     * Return the scope to be used.
     *
     * Either the provided value or default value.
     *
     * @param string $scope The provided scope value.
     * @return string The scope value to be used.
     */
    private function getScope($scope)
    {
        if (is_null($scope)) {
            $scope = Constants::DEFAULT_SCOPE_VALUE;
        }
        return $scope;
    }

    /**
     * Extract the parameters from a url.
     *
     * @param string $url The url to extract parameters from.
     * @return array A list of parameters from the url.
     * @throws OIDCException Throw if the url is invalid.
     */
    private function getParameters($url)
    {
        try {
            return HttpUtils::extractParameters($url);
        } catch (\Exception $ex) {
            throw new OIDCException("Invalid URI", $ex);
        }
    }

    /**
     * Build a HttpPost for the requestToken call.
     *
     * @param string $uri The URI of the token service.
     * @param string $redirectURL Redirect URL required by the token service.
     * @param int $code The code obtained from the authorization service.
     * @return string A url
     * @throws \Exception
     */
    private function buildHttpPostParamsForAccessToken($uri, $redirectURL, $code)
    {
        $uriBuilder = new URIBuilder($uri);

        $uriBuilder->addParameter(Constants::REDIRECT_URI_PARAMETER_NAME, $redirectURL);
        $uriBuilder->addParameter(Constants::GRANT_TYPE_PARAMETER_NAME, Constants::GRANT_TYPE_PARAMETER_VALUE);
        $uriBuilder->addParameter(Constants::CODE_PARAMETER_NAME, $code);

        return $uriBuilder->build();
    }

    /**
     * Converts a rest exception into an OIDC exception
     *
     * @param string $message the exception message
     * @param RestException $restException a rest exception thrown when calling the authorisation service
     * @return OIDCException
     */
    private function newOIDCExceptionFromRestException($message, RestException $restException)
    {
        return new OIDCException($message, $restException->getUri(), $restException->getStatusCode(), $restException->getHeaders(), $restException->getContents(), $restException);
    }

    /**
     * Generate an OIDC exception including the rest response information
     *
     * @param string $message the exception message
     * @param RestResponse $restResponse the response from the authorisation rest endpoint
     * @param \Exception $ex the exception thrown by the rest service
     * @return OIDCException
     */
    private function newOIDCExceptionWithRestResponse($message, RestResponse $restResponse = null, $ex)
    {
        if (is_null($restResponse)) {
            return new OIDCException($message, $ex);
        } else {
            return new OIDCException($message, $restResponse->getUri(), $restResponse->getStatusCode(), $restResponse->getHeaders(), $restResponse->getResponse(), $ex);
        }
    }

}
