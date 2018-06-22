<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * CallbackResponse DTO
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class CallbackResponse
{
    public $mpstatus = null;
    
    public $checkoutResourceUrl = null;
    
    public $oauthVerifier = null;
    
    public $oauthToken = null;
    
    public $pairingVerifier = null;
    
    public $pairingToken = null;
}
