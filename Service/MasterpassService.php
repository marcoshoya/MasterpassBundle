<?php

namespace Hoya\MasterpassBundle\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\Common\BrandInterface;
use Hoya\MasterpassBundle\DTO\AccessTokenResponse;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\Transaction;

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
    const CHECKOUTID = 'checkoutId';
    const MPSTATUS = 'mpstatus';
    
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
     * @var Hoya\MasterpassBundle\Common\BrandInterface
     */
    protected $brand;

    /**
     * @var RequestTokenResponse|null
     */
    protected $requestToken = null;

    /**
     * @param Connector $connector
     */
    public function __construct(Connector $connector, BrandInterface $brand = null)
    {
        $this->connector = $connector;
        $this->brand = $brand;
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
     * Gets checkout id
     * 
     * @return string
     */
    public function getCheckoutId()
    {
        return $this->brand->getCheckoutId();
    }
    
    /**
     * Sets brand
     * 
     * @param BrandInterface $brand
     * 
     * @return \Hoya\MasterpassBundle\Service\MasterpassService
     */
    public function setBrand(BrandInterface $brand)
    {
        $this->brand = $brand;
        
        return $this;
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
            self::OAUTH_VERIFIER => $callback->oauthVerifier,
            self::OAUTH_TOKEN => $callback->oauthToken,
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

        $newShoppingCartXml = $xml->asXML();

        $params = array(
            Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($newShoppingCartXml),
        );

        return $this->connector->doShoppingCart($params, $newShoppingCartXml);
    }
    
    /**
     * Posting Merchant init data do Masterpass services
     * 
     * @param RequestTokenResponse  $requestToken
     * @param string                $initXml
     * 
     * @return string The XML response from MasterCard servicese
     */
    public function postMerchantInitData(RequestTokenResponse $requestToken, $initXml) 
    {
        $xml = simplexml_load_string($initXml);
        $xml->OAuthToken = $requestToken->requestToken;
        $xml->OriginUrl = $this->connector->getOriginUrl();

        $newInitXml = $xml->asXML();

        $params = array(
            Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($newInitXml),
        );

        return $this->connector->doMerchantInit($params, $newInitXml);
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
        $params = array(self::OAUTH_TOKEN => $accessToken->accessToken);

        return $this->connector->doCheckoutData($params, $accessToken->checkoutResourceUrl);
    }

    /**
     * This method submits the receipt transaction list to MasterCard as a final step
     * in the Wallet process.
     *
     * @param Transaction $transaction
     *
     * @return string The XML response from MasterCard services
     */
    public function postTransaction(Transaction $transaction)
    {
        $xmlTransaction = $transaction->toXML();
        $params = array(
            Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($xmlTransaction),
        );

        return $this->connector->doTransaction($params, $xmlTransaction);
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
        $callback->mpstatus = $request->get(self::MPSTATUS);
        $callback->checkoutResourceUrl = $request->get(self::CHECKOUT_RESOURCE_URL);
        $callback->oauthVerifier = $request->get(self::OAUTH_VERIFIER);
        $callback->oauthToken = $request->get(self::OAUTH_TOKEN);
        
        return $callback;
    }

}
