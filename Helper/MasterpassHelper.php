<?php

namespace Hoya\MasterpassBundle\Helper;

/**
 * MasterpassHelper
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class MasterpassHelper
{
    const ENCODED_TILDE = '%7E';
    
    const TILDE = '~';

    public static function formatXML($resources)
    {

        if ($resources != null) {
            $dom = new \DOMDocument;
            $dom->preserveWhiteSpace = FALSE;
            $dom->loadXML($resources);
            $dom->formatOutput = TRUE;
            $resources = $dom->saveXml();
        }
        return $resources;
    }
    
    /**
     * Method to convert strings 'true' and 'false' to a boolean value
     * If parameter string is not 'true' (case insensitive), then false will be returned
     *
     * @param String : $str
     *
     * @return boolean
     */
    public static function str_to_bool($str)
    {
        return (strcasecmp($str, TRUE) == 0) ? true : false;
    }
    
    /**
     * Method to extract the URL parameters and add them to the params array
     * 
     * @param string $urlMap
     * @param string $params
     * 
     * @return string|multitype:
     */
    public static function parseUrlParameters($urlMap, $params)
    {
        if (empty($urlMap['query'])) {

            return $params;
        } else {
            $str = $urlMap['query'];
            parse_str($str, $urlParamsArray);
            foreach ($urlParamsArray as $key => $value) {
                $urlParamsArray[$key] = MasterpassHelper::RFC3986urlencode($value);
            }

            return array_merge($params, $urlParamsArray);
        }
    }

    /**
     * Method to format the URL that is included in the signature base string 
     * 
     * @param string $url
     * @param string $params
     * 
     * @return string|string
     */
    public static function formatUrl($url, $params)
    {
        if (!parse_url($url)) {

            return $url;
        }
        $urlMap = parse_url($url);

        return $urlMap['scheme'] . '://' . $urlMap['host'] . $urlMap['path'];
    }

    /**
     * URLEncoder that conforms to the RFC3986 spec.
     * PHP's internal function, rawurlencode, does not conform to RFC3986 for PHP 5.2
     * 
     * @param unknown $string
     * 
     * @return unknown|mixed
     */
    public static function RFC3986urlencode($string)
    {
        if ($string === false) {

            return $string;
        } else {

            return str_replace(MasterpassHelper::ENCODED_TILDE, MasterpassHelper::TILDE, rawurlencode($string));
        }
    }
}
