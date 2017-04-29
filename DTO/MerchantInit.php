<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * MerchantInit DTO
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class MerchantInit
{
    /**
     * ToXml Merchant Init
     * 
     * @return string
     */
    public function toXML()
    {
        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlrequest = $domtree->createElement('MerchantInitializationRequest');
        $xmlrequest->appendChild($domtree->createElement('OAuthToken'));
        $xmlrequest->appendChild($domtree->createElement('OriginUrl'));
        $domtree->appendChild($xmlrequest);

        return $domtree->saveXML();
    }
}
