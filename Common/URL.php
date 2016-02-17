<?php

namespace Hoya\MasterpassBundle\Common;

class URL
{

    const REQUESTURL = "api.mastercard.com/oauth/consumer/v1/request_token";
    const SHOPPINGCARTURL = "api.mastercard.com/masterpass/v6/shopping-cart";
    const SBX_ACCESSURL = "api.mastercard.com/oauth/consumer/v1/access_token";
    const SBX_POSTBACKURL = "api.mastercard.com/masterpass/v6/transaction";
    const SBX_PRECHECKOUTURL = "api.mastercard.com/masterpass/v6/precheckout";
    const SBX_MERCHANTINITURL = "api.mastercard.com/masterpass/v6/merchant-initialization";
    
    private $deploy;
    
    private $callback;
    
    public function __construct($deploy, $callback)
    {
        $this->deploy = $deploy;
        $this->callback = $callback;
    }
    
    /**
     * Build URL according env
     * 
     * @param sring $url
     * @param boolean $prd
     * 
     * @return string
     */
    private function buildUrl($url)
    {
        return $this->deploy ? sprintf('https://%s', $url) : sprintf('https://sandbox.%s', $url);
    }

    public static function addQueryParameter($url, $descriptor, $value)
    {
        if ($value !== null) {

            return sprintf("%s&%s=%s", $url, $descriptor, rawurlencode($value));
        } else {

            return $url;
        }
    }
    
    public function isProduction()
    {
        return (bool) $this->deploy;
    }
    
    public function getCallbackurl()
    {
        return $this->callback;
    }
    
    /**
     * Get request-token Url
     * 
     * @param boolean $prd
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->buildUrl(self::REQUESTURL);
    }
    
    /**
     * Get shopping-cart Url
     * 
     * @param boolean $prd
     * @return string
     */
    public function getShoppingcartUrl()
    {
        return $this->buildUrl(self::SHOPPINGCARTURL);
    }
    
}
