<?php

namespace Hoya\MasterpassBundle\DTO;

use Hoya\MasterpassBundle\DTO\ShoppingcartItem;

/**
 * Shoppingcart DTO
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class Shoppingcart implements \IteratorAggregate
{

    /**
     * @var string 
     */
    public $currency;

    /**
     * @var array 
     */
    private $itemList = [];

    /**
     * It implements \IteratorAggregate.
     *
     * @see all()
     *
     * @return \ArrayIterator An \ArrayIterator object for iterating over itens
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->itemList);
    }

    /**
     * Get shoppincart item
     * 
     * @param integer $idx
     * @return \Hoya\MasterpassBundle\DTO\ShoppingcartItem
     */
    public function getItem($idx)
    {
        return isset($this->itemList[$idx]) ? $this->itemList[$idx] : null;
    }

    /**
     * Add shoppincart item
     * 
     * @param integer $idx
     * @param ShoppingcartItem $item
     */
    public function addItem($idx, ShoppingcartItem $item)
    {
        unset($this->itemList[$idx]);

        $this->itemList[$idx] = $item;
    }

    /**
     * Remove shoppincart item
     * 
     * @param ShoppingcartItem $item
     */
    public function removeItem(ShoppingcartItem $item)
    {
        $this->itemList = array_diff($this->itemList, array($item));
    }

    /**
     * Get all shoppincart items
     * 
     * @return array
     */
    public function allItem()
    {
        return $this->itemList;
    }

    /**
     * Count shoppincart items
     * 
     * @return integer
     */
    public function countItem()
    {
        return count($this->itemList);
    }

    /**
     * Get shopping-cart amount
     * 
     * @return integer
     */
    public function getAmount()
    {
        $amount = 0;
        if ($this->countItem()) {
            foreach ($this->allItem() as $item) {
                $amount = bcadd($item->getAmount(), $amount);
            }
        }

        return $amount;
    }

    /**
     * ToXml shoppingcart
     * 
     * @return string
     */
    public function toXML()
    {
        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlrequest = $domtree->createElement("ShoppingCartRequest");
        $xmlrequest->appendChild($domtree->createElement("OAuthToken"));
        $xmlcart = $domtree->createElement("ShoppingCart");
        $xmlcart->appendChild($domtree->createElement("CurrencyCode", $this->currency));
        $xmlcart->appendChild($domtree->createElement("Subtotal", $this->getAmount()));

        if ($this->countItem()) {
            foreach ($this->allItem() as $item) {
                $xmlitem = $domtree->createElement("ShoppingCartItem");
                $xmlitem->appendChild($domtree->createElement("Description", $item->description));
                $xmlitem->appendChild($domtree->createElement("Quantity", $item->quantity));
                $xmlitem->appendChild($domtree->createElement("Value", $item->getAmount()));
                $xmlitem->appendChild($domtree->createElement("ImageURL", $item->imageUrl));
                $xmlcart->appendChild($xmlitem);
            }
        }

        $xmlrequest->appendChild($xmlcart);
        $domtree->appendChild($xmlrequest);

        return $domtree->saveXML();
    }

}
