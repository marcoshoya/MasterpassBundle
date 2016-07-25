<?php

namespace Hoya\MasterpassBundle\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\DTO\AccessTokenResponse;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;
use Hoya\MasterpassBundle\DTO\CallbackResponse;

class MasterpassService
{

    //Request Token Response
    const XOAUTH_REQUEST_AUTH_URL = 'xoauth_request_auth_url';
    const OAUTH_CALLBACK_CONFIRMED = 'oauth_callback_confirmed';
    const OAUTH_EXPIRES_IN = 'oauth_expires_in';
    //Request Token Response
    const OAUTH_TOKEN_SECRET = 'oauth_token_secret';
    const ORIGIN_URL = 'origin_url';
    // Callback URL parameters
    const OAUTH_TOKEN = 'oauth_token';
    const OAUTH_VERIFIER = 'oauth_verifier';
    const CHECKOUT_RESOURCE_URL = 'checkout_resource_url';
    const REDIRECT_URL = 'redirect_url';
    const PAIRING_TOKEN = 'pairing_token';
    const PAIRING_VERIFIER = 'pairing_verifier';
    // Redirect Parameters
    const CHECKOUT_IDENTIFIER = 'checkout_identifier';
    const ACCEPTABLE_CARDS = 'acceptable_cards';
    const OAUTH_VERSION = 'oauth_version';
    const VERSION = 'version';
    const SUPPRESS_SHIPPING_ADDRESS = 'suppress_shipping_address';
    const ACCEPT_REWARDS_PROGRAM = 'accept_reward_program';
    const SHIPPING_LOCATION_PROFILE = 'shipping_location_profile';
    const WALLET_SELECTOR = 'wallet_selector_bypass';
    const DEFAULT_XMLVERSION = 'v1';
    const AUTH_LEVEL = 'auth_level';
    const BASIC = 'basic';
    const XML_VERSION_REGEX = '/v[0-9]+/';
    const REALM_TYPE = 'eWallet';
    const APPROVAL_CODE = 'sample';

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var RequestTokenResponse|null
     */
    protected $requestToken;

    /**
     * @param Connector $connector
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
        $this->requestToken = null;
    }

    /**
     * Get connector
     * 
     * @return Hoya\MasterpassBundle\Common\Connector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * SDK:
     * This method captures the Checkout Resource URL and Request Token Verifier
     * and uses these to request the Access Token.
     *
     * @param string $requestToken
     * @param string $verifier
     *
     * @return AccessTokenResponse
     */
    public function getAccessToken(CallbackResponse $callback)
    {
        $params = array(
            self::OAUTH_VERIFIER => $callback->requestVerifier,
            self::OAUTH_TOKEN => $callback->requestToken,
        );

        $accessToken = new AccessTokenResponse();
        $response = $this->connector->doAccessToken($params);
        $responseObject = $this->parseConnectionResponse($response);

        $accessToken->accessToken = isset($responseObject[self::OAUTH_TOKEN]) ? $responseObject[self::OAUTH_TOKEN] : '';
        $accessToken->oAuthSecret = isset($responseObject[self::OAUTH_TOKEN]) ? $responseObject[self::OAUTH_TOKEN_SECRET] : '';
        $accessToken->checkoutResourceUrl = $callback->checkoutResourceUrl;

        return $accessToken;
    }

    /**
     * SDK:
     * This method gets a request token and constructs the redirect URL.
     *
     * @param $acceptableCards
     * @param $checkoutProjectId
     * @param $xmlVersion
     * @param $shippingSuppression
     * @param $rewardsProgram
     * @param $authLevelBasic
     * @param $shippingLocationProfile
     * @param $walletSelector
     *
     * @return RequestTokenResponse
     */
    public function getRequestTokenAndRedirectUrl($acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector)
    {
        $requestToken = $this->getRequestToken();
        $requestToken->redirectUrl = $this->getConsumerSignInUrl($acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector);

        return $requestToken;
    }

    /**
     * SDK:
     * This method posts the Shopping Cart data to MasterCard services
     * and is used to display the shopping cart in the wallet site.
     *
     * @param RequestTokenResponse $requestToken
     * @param string               $shoppingCartXml
     *
     * @return string The XML response from MasterCard services
     */
    public function postShoppingCartData(RequestTokenResponse $requestToken, $shoppingCartXml)
    {
        $xml = simplexml_load_string($shoppingCartXml);
        $xml->OAuthToken = $requestToken->requestToken;
        $xml->OriginUrl = $this->connector->getOriginUrl();

        $newShoppingCartXml = $xml->asXML();

        $params = array(
            Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($newShoppingCartXml),
        );

        return $this->connector->doShoppingCart($params, $newShoppingCartXml);
    }

    /**
     * This method retrieves the payment and shipping information
     * for the current user/session.

     * @param AccessTokenResponse $accessToken
     * 
     * @return string The Checkout XML string containing the users billing and shipping information
     */
    public function getCheckoutData(AccessTokenResponse $accessToken)
    {
        $params = array(self::OAUTH_TOKEN => $accessToken);

        return $this->connector->doRequest($params, $accessToken->checkoutResourceUrl, Connector::GET);
    }

    /**
     * This method submits the receipt transaction list to MasterCard as a final step
     * in the Wallet process.
     *
     * @param string $postBackUrl
     * @param string $merchantTransactions
     *
     * @return string The XML response from MasterCard services
     */
    public function postCheckoutTransaction($postBackUrl, $merchantTransactions)
    {
        $params = array(
            Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($merchantTransactions),
        );

        return $this->connector->doRequest($params, $postBackUrl, Connector::POST, $merchantTransactions);
    }

    /**
     * @param string $preCheckoutUrl
     * @param string $preCheckoutXml
     * @param string $accessToken
     *
     * @return string The XML response from MasterCard services
     */
    public function getPreCheckoutData($preCheckoutUrl, $preCheckoutXml, $accessToken)
    {
        $params = array(
            self::OAUTH_TOKEN => $accessToken,
        );

        return $this->connector->doRequest($params, $preCheckoutUrl, Connector::POST, $preCheckoutXml);
    }

    /**
     * SDK:
     * Get the user's request token and store it in the current user session.
     * 
     * @return RequestTokenResponse
     */
    public function getRequestToken()
    {
        $params = array(
            Connector::OAUTH_CALLBACK => $this->connector->getCallbackUrl(),
        );

        $response = $this->connector->doRequestToken($params, null);
        $requestTokenInfo = $this->parseConnectionResponse($response);

        $requestToken = new RequestTokenResponse();
        $requestToken->requestToken = isset($requestTokenInfo[self::OAUTH_TOKEN]) ? $requestTokenInfo[self::OAUTH_TOKEN] : '';
        $requestToken->authorizeUrl = isset($requestTokenInfo[self::XOAUTH_REQUEST_AUTH_URL]) ? $requestTokenInfo[self::XOAUTH_REQUEST_AUTH_URL] : '';
        $requestToken->callbackConfirmed = isset($requestTokenInfo[self::OAUTH_CALLBACK_CONFIRMED]) ? $requestTokenInfo[self::OAUTH_CALLBACK_CONFIRMED] : '';
        $requestToken->oAuthExpiresIn = isset($requestTokenInfo[self::OAUTH_EXPIRES_IN]) ? $requestTokenInfo[self::OAUTH_EXPIRES_IN] : '';
        $requestToken->oAuthSecret = isset($requestTokenInfo[self::OAUTH_TOKEN_SECRET]) ? $requestTokenInfo[self::OAUTH_TOKEN_SECRET] : '';

        $this->requestToken = $requestToken;

        return $requestToken;
    }

    /**
     * SDK:
     * Assuming that all due diligence is done and assuming the presence of an established session,
     * successful reception of non-empty request token, and absence of any unanticipated
     * exceptions have been successfully verified, you are ready to go to the authorization
     * link hosted by MasterCard.
     *
     * @param $acceptableCards
     * @param $checkoutProjectId
     * @param $xmlVersion
     * @param $shippingSuppression
     * @param $rewardsProgram
     * @param $authLevelBasic
     * @param $shippingLocationProfile
     * @param $walletSelector
     *
     * @return string - URL to redirect the user to the Masterpass wallet site
     *
     * @throws \Exception
     */
    private function getConsumerSignInUrl($acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector)
    {
        if (null === $this->requestToken) {
            throw new \Exception('RequestToken is not known');
        }

        $baseAuthUrl = $this->requestToken->authorizeUrl;

        $xmlVersion = strtolower($xmlVersion);

        // Use v1 if xmlVersion does not match correct patern
        if (!preg_match(self::XML_VERSION_REGEX, $xmlVersion)) {
            $xmlVersion = self::DEFAULT_XMLVERSION;
        }

        $token = $this->requestToken->requestToken;
        if ($token == null || $token == Connector::EMPTY_STRING) {
            throw new \Exception(Connector::EMPTY_REQUEST_TOKEN_ERROR_MESSAGE);
        }

        if ($baseAuthUrl == null || $baseAuthUrl == Connector::EMPTY_STRING) {
            throw new \Exception(Connector::INVALID_AUTH_URL);
        }

        // construct the Redirect URL
        $finalAuthUrl = $baseAuthUrl .
                $this->getParamString(self::ACCEPTABLE_CARDS, $acceptableCards, true) .
                $this->getParamString(self::CHECKOUT_IDENTIFIER, $checkoutProjectId) .
                $this->getParamString(self::OAUTH_TOKEN, $token) .
                $this->getParamString(self::VERSION, $xmlVersion);

        // If xmlVersion is v1 (default version), then shipping suppression, rewardsprogram and auth_level are not used
        if (strcasecmp($xmlVersion, self::DEFAULT_XMLVERSION) != Connector::V1) {
            if ($shippingSuppression == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(self::SUPPRESS_SHIPPING_ADDRESS, $shippingSuppression);
            }

            if ((int) substr($xmlVersion, 1) >= 4 && $rewardsProgram == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(self::ACCEPT_REWARDS_PROGRAM, $rewardsProgram);
            }

            if ($authLevelBasic) {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(self::AUTH_LEVEL, self::BASIC);
            }

            if ((int) substr($xmlVersion, 1) >= 4 && $shippingLocationProfile != null && !empty($shippingLocationProfile)) {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(self::SHIPPING_LOCATION_PROFILE, $shippingLocationProfile);
            }

            if ((int) substr($xmlVersion, 1) >= 5 && $walletSelector == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(self::WALLET_SELECTOR, $walletSelector);
            }
        }

        return $finalAuthUrl;
    }

    /**
     * Method used to parse the connection response and return a array of the data.
     *
     * @param $responseString
     *
     * @return array with all response parameters
     */
    private function parseConnectionResponse($responseString)
    {
        $token = array();
        foreach (explode(Connector::AMP, $responseString) as $p) {
            @list($name, $value) = explode(Connector::EQUALS, $p, 2);
            $token[$name] = urldecode($value);
        }

        return $token;
    }

    /**
     * SDK:
     * Method to create the URL with GET Parameters.
     *
     * @param $key
     * @param $value
     * @param $firstParam
     *
     * @return string
     */
    private function getParamString($key, $value, $firstParam = false)
    {
        $paramString = Connector::EMPTY_STRING;

        if ($firstParam) {
            $paramString .= Connector::QUESTION;
        } else {
            $paramString .= Connector::AMP;
        }
        $paramString .= $key . Connector::EQUALS . $value;

        return $paramString;
    }

    /**
     * Check for errors
     * @return boolean
     */
    public function hasError()
    {
        return $this->connector->errorMessage !== null ? true : false;
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->connector->errorMessage;
    }

    /**
     * Handle callback
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return CallbackResponse
     */
    public function handleCallback(\Symfony\Component\HttpFoundation\Request $request)
    {
        $callback = new CallbackResponse;
        $callback->requestToken = $request->get(self::OAUTH_TOKEN);
        $callback->requestVerifier = $request->get(self::OAUTH_VERIFIER);
        $callback->checkoutResourceUrl = $request->get(self::CHECKOUT_RESOURCE_URL);

        return $callback;
    }

}
