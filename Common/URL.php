<?php

namespace Hoya\MasterpassBundle\Common;

class URL
{
    const REQUESTURL = 'api.mastercard.com/oauth/consumer/v1/request_token';
    const SHOPPINGCARTURL = 'api.mastercard.com/masterpass/v6/shopping-cart';
    const ACCESSURL = 'api.mastercard.com/oauth/consumer/v1/access_token';
    const SBX_POSTBACKURL = 'api.mastercard.com/masterpass/v6/transaction';
    const SBX_PRECHECKOUTURL = 'api.mastercard.com/masterpass/v6/precheckout';
    const SBX_MERCHANTINITURL = 'api.mastercard.com/masterpass/v6/merchant-initialization';

    /**
     * @var bool
     */
    private $productionMode;

    /**
     * @var string
     */
    private $callback;

    /**
     * @param bool   $productionMode
     * @param string $callback
     */
    public function __construct($productionMode, $callback)
    {
        $this->productionMode = $productionMode;
        $this->callback = $callback;
    }

    /**
     * Verifies if is production environment or not.
     * 
     * @return bool
     */
    public function isProduction()
    {
        return (bool) $this->productionMode;
    }

    /**
     * Gets callback url.
     * 
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callback;
    }

    /**
     * Get request-token Url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->buildUrl(self::REQUESTURL);
    }

    /**
     * Get shopping-cart Url.
     *
     * @return string
     */
    public function getShoppingcartUrl()
    {
        return $this->buildUrl(self::SHOPPINGCARTURL);
    }

    /**
     * @return string
     */
    public function getAccessUrl()
    {
        return $this->buildUrl(self::ACCESSURL);
    }

    /**
     * @return string
     */
    public function getOriginUrl()
    {
        // @TODO
        return 'http://localhost';
    }

    /**
     * Build URL according env.
     *
     * @param string $url
     *
     * @return string
     */
    private function buildUrl($url)
    {
        return $this->productionMode ? sprintf('https://%s', $url) : sprintf('https://sandbox.%s', $url);
    }
}
