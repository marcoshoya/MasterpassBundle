<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * ShoppingcartItem DTO
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class ShoppingcartItem
{

    /**
     * @var integer
     */
    public $quantity;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $imageUrl;

    /**
     * @var float
     */
    private $amount;

    /**
     * Set item amount and format without decimal points
     * 
     * @param float $amount
     */
    public function setAmount($amount = 0.00)
    {
        if (strpos(',', $amount)) {
            $amount = floatval($amount);
        }
        $this->amount = number_format($amount, 2, '', '');
    }

    /**
     * Get Amount
     * 
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
