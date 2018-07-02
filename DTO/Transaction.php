<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * Description of Transaction
 *
 * @author Marcos
 */
class Transaction
{
    /**
     * @var int
     */
    public $transactionId;

    /**
     * @var string ISO-4217 code for currency of the transaction.
     */
    public $currency;

    /**
     * @var float
     */
    private $orderAmount;

    /**
     * @var \DateTime
     */
    private $purchaseDate;

    /**
     * @var boolean
     */
    public $paymentSuccessful;

    /**
     * @var string The six-digit approval code returned by payment API.
     */
    public $paymentCode;
    
    /**
     * @var string
     */
    public $preCheckoutTransactionId;

    /**
     * Set purchase
     * @param \DateTime $date
     */
    public function setPurchaseDate(\DateTime $date)
    {
        $this->purchaseDate = $date;
    }

    /**
     * @return \DateTime
     */
    public function getPurchaseDate()
    {
        return $this->purchaseDate;
    }

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
            'transactionId' => $this->transactionId,
            'currency' => $this->currency,
            'amount' => $this->getAmount(),
            'paymentSuccessful' => $this->paymentSuccessful,
            'paymentCode' => $this->paymentCode,
            'paymentDate' => $this->getPurchaseDate()->format(\DateTime::ATOM),
            'preCheckoutTransactionId' => $this->preCheckoutTransactionId
        ]);
    }

}
