<?php

namespace Hoya\MasterpassBundle\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\Common\BrandInterface;
use Hoya\MasterpassBundle\DTO\CallbackResponse;

class MasterpassService 
{

    // Callback URL parameters
    const OAUTH_TOKEN = 'oauth_token';
    const OAUTH_VERIFIER = 'oauth_verifier';
    const CHECKOUT_RESOURCE_URL = 'checkout_resource_url';
    const REDIRECT_URL = 'redirect_url';
    const PAIRING_TOKEN = 'pairing_token';
    const PAIRING_VERIFIER = 'pairing_verifier';
    const CHECKOUTID = 'checkoutId';
    const MPSTATUS = 'mpstatus';

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var Hoya\MasterpassBundle\Common\BrandInterface
     */
    protected $brand;

    /**
     * @param Connector $connector
     */
    public function __construct(Connector $connector, BrandInterface $brand = null)
    {
        $this->connector = $connector;
        $this->brand = $brand;
    }

    /**
     * Get connector
     * 
     * @return Hoya\MasterpassBundle\Common\Connector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Gets checkout id
     * 
     * @return string
     */
    public function getCheckoutId()
    {
        return $this->brand->getCheckoutId();
    }

    /**
     * Sets brand
     * 
     * @param BrandInterface $brand
     * 
     * @return \Hoya\MasterpassBundle\Service\MasterpassService
     */
    public function setBrand(BrandInterface $brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Check for errors
     * @return boolean
     */
    public function hasError()
    {
        return $this->connector->errorMessage !== null ? true : false;
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->connector->errorMessage;
    }

    /**
     * Handle callback
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return CallbackResponse
     */
    public function handleCallback(\Symfony\Component\HttpFoundation\Request $request)
    {
        $callback = new CallbackResponse;
        $callback->mpstatus = $request->get(self::MPSTATUS);
        $callback->checkoutResourceUrl = $request->get(self::CHECKOUT_RESOURCE_URL);
        $callback->oauthVerifier = $request->get(self::OAUTH_VERIFIER);
        $callback->oauthToken = $request->get(self::OAUTH_TOKEN);

        return $callback;
    }

}
