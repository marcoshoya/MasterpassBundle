<?php

namespace Hoya\MasterpassBundle\Common;

class URLUtil
{

    public static function addQueryParameter($url, $descriptor, $value)
    {
        if ($value !== null) {
            
            return sprintf("%s&%s=%s", $url, $descriptor, rawurlencode($value));
        } else {
            
            return $url;
        }
    }

}
