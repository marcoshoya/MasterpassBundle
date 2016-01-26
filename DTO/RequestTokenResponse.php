<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * DTO:
 * Holds data relevant to the Request Token
 */
class RequestTokenResponse
{

    public $requestToken;
    public $authorizeUrl;
    public $callbackConfirmed;
    public $oAuthExpiresIn;
    public $oAuthSecret;
    public $redirectUrl;

}
