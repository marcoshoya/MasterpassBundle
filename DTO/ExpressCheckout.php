<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * ExpressCheckout DTO
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class ExpressCheckout 
{
    /**
     * @var string
     */
    public $checkoutId = null;
    
    /**
     * @var string
     */
    public $pairingId = null;
    
    /**
     * @var string
     */
    public $preCheckoutTransactionId = null;
    
    /**
     * @var string ISO-4217 code for currency of the transaction.
     */
    public $currency = null;
    
    /**
     * @var string
     */
    public $cardId = null;
    
    /**
     * @var string
     */
    public $shippingAddressId = null;
    
    /**
     * @var boolean
     */
    public $digitalGoods = false;
    
    /**
     * @var float
     */
    private $orderAmount;
    
    /**
     * @var string
     */
    public $pspId = null;
    
    /**
     * Set transaction amount and format without decimal points.
     * 
     * @param float $amount
     */
    public function setAmount($amount = 0.00)
    {
        if (strpos(',', $amount)) {
            $amount = floatval($amount);
        }
        $this->orderAmount = number_format($amount, 2, '', '');
    }

    /**
     * Get Amount.
     * 
     * @return int
     */
    public function getAmount()
    {
        return $this->orderAmount;
    }
    
    /**
     * toJSON object
     * 
     * @return string|json
     */
    public function toJSON()
    {
        return json_encode([
            'checkoutId' => $this->checkoutId,
            'pairingId' => $this->pairingId,
            'preCheckoutTransactionId' => $this->preCheckoutTransactionId,
            'amount' => $this->getAmount(),
            'currency' => $this->currency,
            'cardId' => $this->cardId,
            'shippingAddressId' => $this->shippingAddressId,
            'digitalGoods' => $this->digitalGoods,
            'pspId' => $this->pspId
        ]);
    }
}
