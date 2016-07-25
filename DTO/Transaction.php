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
     * @var string
     */
    public $consumerKey;

    /**
     * @var string
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
     * @var string
     */
    public $transactionStatus;

    /**
     * @var string
     */
    public $approvalCode;

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
     * ToXml shoppingcart.
     * 
     * @return string
     */
    public function toXML()
    {
        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlrequest = $domtree->createElement('MerchantTransactions');

        $transaction = $domtree->createElement('MerchantTransactions');
        $transaction->appendChild($domtree->createElement('TransactionId', $this->transactionId));
        $transaction->appendChild($domtree->createElement('ConsumerKey', $this->consumerKey));
        $transaction->appendChild($domtree->createElement('Currency', $this->currency));
        $transaction->appendChild($domtree->createElement('OrderAmount', $this->getAmount()));
        $transaction->appendChild($domtree->createElement('PurchaseDate', $this->getPurchaseDate()->format('Y-m-d h:i:s')));
        $transaction->appendChild($domtree->createElement('TransactionStatus', $this->transactionStatus));
        $transaction->appendChild($domtree->createElement('ApprovalCode', $this->approvalCode));

        $xmlrequest->appendChild($transaction);
        $domtree->appendChild($xmlrequest);

        return $domtree->saveXML();
    }

}
