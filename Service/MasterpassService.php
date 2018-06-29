<?php

namespace Hoya\MasterpassBundle\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\Common\BrandInterface;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\Transaction;
use Hoya\MasterpassBundle\DTO\ExpressCheckout;

/**
 * MasterpassService class
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class MasterpassService 
{
    // Callback URL parameters
    const OAUTH_TOKEN = 'oauth_token';
    const OAUTH_VERIFIER = 'oauth_verifier';
    const PAIRING_TOKEN = 'pairing_token';
    const PAIRING_VERIFIER = 'pairing_verifier';
    const MPSTATUS = 'mpstatus';

    /**
     * @var Hoya\MasterpassBundle\Common\Connector
     */
    protected $connector;

    /**
     * @var Hoya\MasterpassBundle\Common\BrandInterface
     */
    protected $brand;

    /**
     * Masterpass Service class
     * 
     * @param BrandInterface $brand
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
        $callback->oauthVerifier = $request->get(self::OAUTH_VERIFIER);
        $callback->oauthToken = $request->get(self::OAUTH_TOKEN);
        $callback->pairingVerifier = $request->get(self::PAIRING_VERIFIER);
        $callback->pairingToken = $request->get(self::PAIRING_TOKEN);

        return $callback;
    }
    
    /**
     * Call PaymentData API
     * 
     * @param CallbackResponse $callback
     * @param string $cartId
     * 
     * @return json|string
     * 
     * @throws Exception
     */
    public function getPaymentData(CallbackResponse $callback, $cartId = null)
    {
        if (!$callback->oauthToken) {
            throw new \Exception("oauthToken cannot be null");
        }
        
        return $this->connector->doPaymentData($callback->oauthToken, $cartId, $this->getCheckoutId());
        
    }
    
    /**
     * This method submits the receipt transaction list to MasterCard as a final step
     * in the Wallet process.
     *
     * @param Transaction $transaction
     *
     * @return string The XML response from MasterCard services
     */
    public function postTransaction(Transaction $transaction)
    {
        $body = $transaction->toJSON();
        $params = [Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($body)];
            
        return $this->connector->doTransaction($params, $body);
    }
    
    /**
     * Call EncryptedData API
     * 
     * @param CallbackResponse $callback
     * @param string $cartId
     * 
     * @return json|string
     * 
     * @throws Exception
     */
    public function getEncryptedData(CallbackResponse $callback, $cartId = null)
    {
        if (!$callback->oauthToken) {
            throw new \Exception("oauthToken cannot be null");
        }
        
        return $this->connector->doEncryptedData($callback->oauthToken, $cartId, $this->getCheckoutId());
    }
    
    /**
     * Decrypts API response using JOSE library
     * 
     * @param string $response Encrypted payload response
     * 
     * @return json|null
     */
    public function decryptResponse($response)
    {
        $json = json_decode($response);
        
        return $this->connector->decryptResponse($json->encryptedPaymentData);
    }
    
    /**
     * Call Pairing API
     * 
     * @param CallbackResponse $callback
     * @param string $userId
     * 
     * @return json|string
     * 
     * @throws Exception
     */
    public function getPairingData(CallbackResponse $callback, $userId = null)
    {
        if (!$callback->pairingVerifier) {
            throw new \Exception("pairingVerifier cannot be null");
        }
        
        return $this->connector->doPairingData($callback->pairingVerifier, $userId);
    }
    
    /**
     * Call Precheckout API
     * 
     * @param string $precheckoutId
     * 
     * @return json|string
     * 
     * @throws Exception
     */
    public function getPrecheckoutData($precheckoutId)
    {
        if (!$precheckoutId) {
            throw new \Exception("precheckoutId cannot be null");
        }
        
        return $this->connector->doPrecheckoutData($precheckoutId);
    }
    
    public function postExpressCheckout(ExpressCheckout $express)
    {
        $body = $express->toJSON();
        $params = [Connector::OAUTH_BODY_HASH => $this->connector->generateBodyHash($body)];
            
        return $this->connector->doExpressCheckout($params, $body);
    }
    
    /**
     * Call PSP payment data API
     * 
     * @param string $tid
     * 
     * @return string|json
     */
    public function getPspPaymentData($tid) 
    {
        return $this->connector->doPspPaymentData($tid);
        
    }
}
