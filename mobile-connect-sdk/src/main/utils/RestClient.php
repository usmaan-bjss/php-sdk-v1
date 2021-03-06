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

use MCSDK\helpers\MobileConnectInterface;
use Zend\Http\Client;
use Zend\Http\Response;

/**
 * Class to make Rest requests.
 */
class RestClient
{

    const POST_IDENTIFYING_REGEX = '/accesstoken/';
    const EXCEPTION_INVALID_RESPONSE = "Invalid response";

    /**
     * Build a Client instance that will authenticate using Basic Authentication
     *
     * @param string $username Username for credentials
     * @param string $password Password for credentials
     * @param string $uriForRealm uri used to determine the authentication realm
     * @return Client A Zend Client instance that will authenticate using Basic Authentication
     */
    public function getHttpClientContext($username, $password, $uriForRealm)
    {
        $context = new Client();
        $context->setUri($uriForRealm);
        $context->setAuth($username, $password);

        return $context;
    }

    /**
     * Make the specified request with the specified client context.
     *
     * The specified cookies will be added to the request. A request will be aborted if it exceeds the specified timeout.
     * Non Json responses are converted and thrown as RestExceptions.
     *
     * Ensures that all closable resources are closed.
     *
     * @param Client $context the zend rest client
     * @param string $uri the uri to use when send requests to GSMA
     * @param string $httpPath the rest path string for instance discovery/test/
     * @param array $params the GET/POST parameters to include in the request
     * @param array $extraHeaders Extra headers to use.
     * @param int $timeout the request timeout in milliseconds
     * @param array $currentCookies the cookies to send to and from GSMA api
     * @return RestResponse the response or an exception on failure
     * @throws \Exception when the rest request has failed in some way
     * @throws RestException Custom exception in the event that the rest service is not responding as expected
     */
    public function callRestEndPoint(Client $context, $uri, $httpPath, array $params, array $extraHeaders, $timeout, array $currentCookies = null)
    {
        if (!is_null($currentCookies)) {
            $context->setCookies($currentCookies);
        }

        $httpResponse = $this->executeRequest($context, $uri . $httpPath, $params, $extraHeaders, $timeout);

        try {
            $restResponse = $this->buildRestResponse($uri, $httpResponse);
            self::checkRestResponse($restResponse);

            return $restResponse;
        } catch (\Exception $e) {

            throw $e;
        }
    }

    /**
     * Execute the given REST request.
     *
     * Abort the request if it exceeds the specified timeout.
     *
     * @param Client $context The client to use.
     * @param string $uri The request uri.
     * @param array $params The parameters to use.
     * @param array $extraHeaders Extra headers to use.
     * @param int $timeout The timeout in milliseconds to use.
     * @return Response A Http Response.
     * @throws RestException Thrown if the request fails or times out.
     */
    private function executeRequest(Client $context, $uri, array $params, array $extraHeaders, $timeout)
    {
        $context->setOptions(array(Constants::REST_TIMEOUT_KEY => ($timeout / 1000)));

        if (preg_match(self::POST_IDENTIFYING_REGEX, $uri)) {
            $context->setMethod(Constants::HTTP_POST_KEY)
                ->setOptions(array(Constants::REST_SSL_VERIFY_OPTION => false))
                ->setParameterPost($params);

            $headers = array(Constants::ACCEPT_HEADER_NAME => Constants::ACCEPT_JSON_HEADER_VALUE,
                Constants::CONTENT_TYPE_HEADER_NAME => Constants::CONTENT_TYPE_HEADER_VALUE);
            foreach ($extraHeaders as $item) {
                array_push($headers, $item);
            }
        } else {
            $context->setMethod(Constants::HTTP_GET_KEY)
                ->setParameterGet($params);

            $headers = array(Constants::ACCEPT_HEADER_NAME => Constants::ACCEPT_JSON_HEADER_VALUE,
                Constants::CONTENT_TYPE_HEADER_NAME => Constants::ACCEPT_JSON_HEADER_VALUE);
            foreach ($extraHeaders as $item) {
                array_push($headers, $item);
            }
        }

        if (count($context->getCookies()) > 0) {
            $restCookies = $context->getCookies();

            foreach ($restCookies as $cookie) {
                $headers[MobileConnectInterface::SET_COOKIE_HEADER_WRITE] = array($cookie->getName() => $cookie->getValue());
            }
        }

        $context->setHeaders($headers);
        $context->setUri($uri);
        $response = $context->send();

        return $response;
    }

    /**
     * Builds a RestResponse from the given HttpResponse
     *
     * @param string $requestUri The original request.
     * @param Response $httpResponse The HttpResponse to build the RestResponse for.
     * @return RestResponse The RestResponse of the HttpResponse
     * @throws \Exception
     */
    private function buildRestResponse($requestUri, Response $httpResponse)
    {
        $statusCode = $httpResponse->getStatusCode();
        $headerList = $httpResponse->getHeaders();
        $responseData = $httpResponse->getBody();

        return new RestResponse($requestUri, $statusCode, $headerList, $responseData);
    }

    /**
     * Checks the Rest Response converting it to an exception if necessary.
     *
     * Non Json responses are converted to exceptions.
     *
     * @param RestResponse $response The Response to check.
     * @throws RestException
     */
    private static function checkRestResponse(RestResponse $response)
    {
        if (!$response->isJsonContent()) {
            throw new RestException(self::EXCEPTION_INVALID_RESPONSE, null, null, $response);
        }
    }

}
