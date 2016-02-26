<?php

namespace Hoya\MasterpassBundle\Common;

class URL
{

    const REQUESTURL = "api.mastercard.com/oauth/consumer/v1/request_token";
    const SHOPPINGCARTURL = "api.mastercard.com/masterpass/v6/shopping-cart";
    const ACCESSURL = "api.mastercard.com/oauth/consumer/v1/access_token";
    const SBX_POSTBACKURL = "api.mastercard.com/masterpass/v6/transaction";
    const SBX_PRECHECKOUTURL = "api.mastercard.com/masterpass/v6/precheckout";
    const SBX_MERCHANTINITURL = "api.mastercard.com/masterpass/v6/merchant-initialization";

    private $productionMode;
    private $callback;

    public function __construct($productionMode, $callback)
    {
        $this->productionMode = $productionMode;
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
        return $this->productionMode ? sprintf('https://%s', $url) : sprintf('https://sandbox.%s', $url);
    }

    public static function addQueryParameter($url, $descriptor, $value)
    {
        if ($value !== null) {

            return sprintf("%s&%s=%s", $url, $descriptor, rawurlencode($value));
        } else {

            return $url;
        }
    }

    /**
     * Verifies if is production environment or not
     * 
     * @return boolean
     */
    public function isProduction()
    {
        return (bool) $this->productionMode;
    }

    /**
     * Gets callback url
     * 
     * @return string
     */
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
    
    
    public function getAccessUrl()
    {
        return $this->buildUrl(self::ACCESSURL);
    }
    
    
    public function getOriginUrl()
    {
        // @TODO
        return 'http://localhost';
    }

}
