<?php

namespace Hoya\MasterpassBundle\Common;

class URL
{

    const SBX_REQUESTURL = "https://sandbox.api.mastercard.com/oauth/consumer/v1/request_token";
    const SBX_SHOPPINGCARTURL = "https://sandbox.api.mastercard.com/masterpass/v6/shopping-cart";
    const SBX_ACCESSURL = "https://sandbox.api.mastercard.com/oauth/consumer/v1/access_token";
    const SBX_POSTBACKURL = "https://sandbox.api.mastercard.com/masterpass/v6/transaction";
    const SBX_PRECHECKOUTURL = "https://sandbox.api.mastercard.com/masterpass/v6/precheckout";
    const SBX_MERCHANTINITURL = "https://sandbox.api.mastercard.com/masterpass/v6/merchant-initialization";
    
    const PRD_REQUESTURL = "https://api.mastercard.com/oauth/consumer/v1/request_token";
    const PRD_SHOPPINGCARTURL = "https://api.mastercard.com/masterpass/v6/shopping-cart";
    const PRD_ACCESSURL = "https://api.mastercard.com/oauth/consumer/v1/access_token";
    const PRD_POSTBACKURL = "https://api.mastercard.com/masterpass/v6/transaction";
    const PRD_PRECHECKOUTURL = "https://api.mastercard.com/masterpass/v6/precheckout";
    const PRD_MERCHANTINITURL = "https://api.mastercard.com/masterpass/v6/merchant-initialization";

    public static function addQueryParameter($url, $descriptor, $value)
    {
        if ($value !== null) {

            return sprintf("%s&%s=%s", $url, $descriptor, rawurlencode($value));
        } else {

            return $url;
        }
    }
}
