<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * URL Helper
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class URL
{
    const PAYMENTDATAURL = 'api.mastercard.com/masterpass/paymentdata';
    const POSTBACKURL = 'api.mastercard.com/masterpass/postback';
    const LIGHTBOXURL = 'masterpass.com/integration/merchant.js';

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
     * @param string $originUrl
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
     * Get lightbox url.
     *
     * @return string
     */
    public function getLightboxUrl()
    {
        return $this->buildUrl(self::LIGHTBOXURL);
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
    
    /**
     * Get payment data url
     * 
     * @param string $tid
     * @param string $cartid
     * @param string $checkoutid
     * 
     * @return string
     */
    public function getPaymentdataUrl($tid, $cartid, $checkoutid)
    {
        $url = $this->buildUrl(self::PAYMENTDATAURL);
        
        return sprintf('%s/%s?checkoutId=%s&cartId=%s', $url, $tid, $checkoutid, $cartid);
    }
    
    /**
     * Get postback url
     * 
     * @return string
     */
    public function getTransactionUrl()
    {
        return $this->buildUrl(self::POSTBACKURL);
    }
}
