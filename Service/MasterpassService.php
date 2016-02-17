<?php

namespace Hoya\MasterpassBundle\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\DTO\AccessTokenResponse;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;

class MasterpassService extends Connector
{

    //Request Token Response
    const XOAUTH_REQUEST_AUTH_URL = "xoauth_request_auth_url";
    const OAUTH_CALLBACK_CONFIRMED = "oauth_callback_confirmed";
    const OAUTH_EXPIRES_IN = "oauth_expires_in";
    //Request Token Response
    const OAUTH_TOKEN_SECRET = "oauth_token_secret";
    const ORIGIN_URL = "origin_url";
    // Callback URL parameters
    const OAUTH_TOKEN = "oauth_token";
    const OAUTH_VERIFIER = "oauth_verifier";
    const CHECKOUT_RESOURCE_URL = "checkout_resource_url";
    const REDIRECT_URL = "redirect_url";
    const PAIRING_TOKEN = "pairing_token";
    const PAIRING_VERIFIER = "pairing_verifier";
    // Redirect Parameters
    const CHECKOUT_IDENTIFIER = 'checkout_identifier';
    const ACCEPTABLE_CARDS = 'acceptable_cards';
    const OAUTH_VERSION = 'oauth_version';
    const VERSION = 'version';
    const SUPPRESS_SHIPPING_ADDRESS = 'suppress_shipping_address';
    const ACCEPT_REWARDS_PROGRAM = 'accept_reward_program';
    const SHIPPING_LOCATION_PROFILE = 'shipping_location_profile';
    const WALLET_SELECTOR = 'wallet_selector_bypass';
    const DEFAULT_XMLVERSION = "v1";
    const AUTH_LEVEL = "auth_level";
    const BASIC = "basic";
    const XML_VERSION_REGEX = "/v[0-9]+/";
    const REALM_TYPE = "eWallet";
    const APPROVAL_CODE = "sample";

    public $originUrl;

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * SDK:
     * This method captures the Checkout Resource URL and Request Token Verifier
     * and uses these to request the Access Token.
     * @param $requestToken
     * @param $verifier
     * @return Output is Access Token
     */
    public function GetAccessToken($accessUrl, $requestToken, $verifier)
    {
        $params = array(
            MasterPassService::OAUTH_VERIFIER => $verifier,
            MasterPassService::OAUTH_TOKEN => $requestToken
        );

        $return = new AccessTokenResponse();
        $response = $this->doRequest($params, $accessUrl, Connector::POST, null);
        $responseObject = $this->parseConnectionResponse($response);

        $return->accessToken = isset($responseObject[MasterPassService::OAUTH_TOKEN]) ? $responseObject[MasterPassService::OAUTH_TOKEN] : "";
        $return->oAuthSecret = isset($responseObject[MasterPassService::OAUTH_TOKEN]) ? $responseObject[MasterPassService::OAUTH_TOKEN_SECRET] : "";
        return $return;
    }

    /**
     * SDK:
     * This method gets a request token and constructs the redirect URL
     * @param $requestUrl
     * @param $callbackUrl
     * @param $acceptableCards
     * @param $checkoutProjectId
     * @param $xmlVersion
     * @param $shippingSuppression
     * @param $rewardsProgram
     * @param $authLevelBasic
     * @param $shippingLocationProfile
     * @param $walletSelector
     * @return Output is a RequestTokenResponse object containing all data returned from this method
     */
    public function getRequestTokenAndRedirectUrl($requestUrl, $callbackUrl, $acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector)
    {
        $return = $this->getRequestToken($requestUrl, $callbackUrl);
        $return->redirectURL = $this->getConsumerSignInUrl($acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector);
        return $return;
    }

    /**
     * Method used to parse the connection response and return a array of the data
     *
     * @param $responseString
     *
     * @return Array with all response parameters
     */
    public function parseConnectionResponse($responseString)
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
     * This method posts the Shopping Cart data to MasterCard services
     * and is used to display the shopping cart in the wallet site.
     * @param $ShoppingCartXml
     * @return Output is the response from MasterCard services
     */
    public function postShoppingCartData($shoppingCartUrl, $shoppingCartXml)
    {
        $params = array(
            Connector::OAUTH_BODY_HASH => $this->generateBodyHash($shoppingCartXml)
        );
        $response = $this->doRequest($params, $shoppingCartUrl, Connector::POST, $shoppingCartXml);
        return $response;
    }

    public function postMerchantInitData($merchantInitUrl, $merchantInitXml)
    {
        $params = array(
            Connector::OAUTH_BODY_HASH => $this->generateBodyHash($merchantInitXml)
        );
        $response = $this->doRequest($params, $merchantInitUrl, Connector::POST, $merchantInitXml);
        return $response;
    }

    /**
     * SDK:
     * This method retrieves the payment and shipping information
     * for the current user/session.
     * @param unknown $accessToken
     * @param unknown $checkoutResourceUrl
     * @return Output is the Checkout XML string containing the users billing and shipping information
     */
    public function GetPaymentShippingResource($checkoutResourceUrl, $accessToken)
    {
        $params = array(
            MasterPassService::OAUTH_TOKEN => $accessToken
        );

        $response = $this->doRequest($params, $checkoutResourceUrl, Connector::GET, null);
        return $response;
    }

    /**
     * This method submits the receipt transaction list to MasterCard as a final step
     * in the Wallet process.
     * @param $merchantTransactions
     * @return Output is the response from MasterCard services
     */
    public function PostCheckoutTransaction($postbackurl, $merchantTransactions)
    {
        $params = array(
            Connector::OAUTH_BODY_HASH => $this->generateBodyHash($merchantTransactions)
        );

        $response = $this->doRequest($params, $postbackurl, Connector::POST, $merchantTransactions);

        return $response;
    }

    public function getPreCheckoutData($preCheckoutUrl, $preCheckoutXml, $accessToken)
    {
        $params = array(
            MasterPassService::OAUTH_TOKEN => $accessToken
        );
        $response = $this->doRequest($params, $preCheckoutUrl, Connector::POST, $preCheckoutXml);
        return $response;
    }

    protected function OAuthParametersFactory()
    {

        $params = parent::OAuthParametersFactory();

        $params[MasterPassService::ORIGIN_URL] = $this->originUrl;

        return $params;
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
            Connector::OAUTH_CALLBACK => $this->urlService->getCallbackUrl()
        );

        $response = $this->doRequest($params, $this->urlService->getRequestUrl(), Connector::POST, null);
        $requestTokenInfo = $this->parseConnectionResponse($response);

        $return = new RequestTokenResponse();
        $return->requestToken = isset($requestTokenInfo[MasterPassService::OAUTH_TOKEN]) ? $requestTokenInfo[MasterPassService::OAUTH_TOKEN] : '';
        $return->authorizeUrl = isset($requestTokenInfo[MasterPassService::XOAUTH_REQUEST_AUTH_URL]) ? $requestTokenInfo[MasterPassService::XOAUTH_REQUEST_AUTH_URL] : '';
        $return->callbackConfirmed = isset($requestTokenInfo[MasterPassService::OAUTH_CALLBACK_CONFIRMED]) ? $requestTokenInfo[MasterPassService::OAUTH_CALLBACK_CONFIRMED] : '';
        $return->oAuthExpiresIn = isset($requestTokenInfo[MasterPassService::OAUTH_EXPIRES_IN]) ? $requestTokenInfo[MasterPassService::OAUTH_EXPIRES_IN] : '';
        $return->oAuthSecret = isset($requestTokenInfo[MasterPassService::OAUTH_TOKEN_SECRET]) ? $requestTokenInfo[MasterPassService::OAUTH_TOKEN_SECRET] : '';

        $this->requestTokenInfo = $return;

        // Return the request token response class.
        return $return;
    }

    /**
     * SDK:
     * Assuming that all due diligence is done and assuming the presence of an established session,
     * successful reception of non-empty request token, and absence of any unanticipated
     * exceptions have been successfully verified, you are ready to go to the authorization
     * link hosted by MasterCard.
     * @param $acceptableCards
     * @param $checkoutProjectId
     * @param $xmlVersion
     * @param $shippingSuppression
     * @param $rewardsProgram
     * @param $authLevelBasic
     * @param $shippingLocationProfile
     * @param $walletSelector
     *
     * @return string - URL to redirect the user to the MasterPass wallet site
     */
    private function GetConsumerSignInUrl($acceptableCards, $checkoutProjectId, $xmlVersion, $shippingSuppression, $rewardsProgram, $authLevelBasic, $shippingLocationProfile, $walletSelector)
    {
        $baseAuthUrl = $this->requestTokenInfo->authorizeUrl;

        $xmlVersion = strtolower($xmlVersion);

        // Use v1 if xmlVersion does not match correct patern
        if (!preg_match(MasterPassService::XML_VERSION_REGEX, $xmlVersion)) {
            $xmlVersion = MasterPassService::DEFAULT_XMLVERSION;
        }

        $token = $this->requestTokenInfo->requestToken;
        if ($token == null || $token == Connector::EMPTY_STRING) {
            throw new \Exception(Connector::EMPTY_REQUEST_TOKEN_ERROR_MESSAGE);
        }

        if ($baseAuthUrl == null || $baseAuthUrl == Connector::EMPTY_STRING) {
            throw new \Exception(Connector::INVALID_AUTH_URL);
        }

        // construct the Redirect URL
        $finalAuthUrl = $baseAuthUrl .
            $this->getParamString(MasterPassService::ACCEPTABLE_CARDS, $acceptableCards, true) .
            $this->getParamString(MasterPassService::CHECKOUT_IDENTIFIER, $checkoutProjectId) .
            $this->getParamString(MasterPassService::OAUTH_TOKEN, $token) .
            $this->getParamString(MasterPassService::VERSION, $xmlVersion);

        // If xmlVersion is v1 (default version), then shipping suppression, rewardsprogram and auth_level are not used
        if (strcasecmp($xmlVersion, MasterPassService::DEFAULT_XMLVERSION) != Connector::V1) {

            if ($shippingSuppression == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(MasterPassService::SUPPRESS_SHIPPING_ADDRESS, $shippingSuppression);
            }

            if ((int) substr($xmlVersion, 1) >= 4 && $rewardsProgram == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(MasterPassService::ACCEPT_REWARDS_PROGRAM, $rewardsProgram);
            }

            if ($authLevelBasic) {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(MasterPassService::AUTH_LEVEL, MasterPassService::BASIC);
            }

            if ((int) substr($xmlVersion, 1) >= 4 && $shippingLocationProfile != null && !empty($shippingLocationProfile)) {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(MasterPassService::SHIPPING_LOCATION_PROFILE, $shippingLocationProfile);
            }

            if ((int) substr($xmlVersion, 1) >= 5 && $walletSelector == 'true') {
                $finalAuthUrl = $finalAuthUrl . $this->getParamString(MasterPassService::WALLET_SELECTOR, $walletSelector);
            }
        }
        return $finalAuthUrl;
    }

    /**
     * SDK:
     * Method to create the URL with GET Parameters
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

}