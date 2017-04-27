<?php

namespace Hoya\MasterpassBundle\DTO;

/**
 * MasterpassOrder DTO
 * 
 * This DTO must be used to carry the order data through checkout flow
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class MasterpassOrder
{

    /**
     * @var string order id
     */
    public $id = null;

    /**
     * @var \Hoya\MasterpassBundle\DTO\Shoppingcart
     */
    public $shoppingCart;

    /**
     * @var \Hoya\MasterpassBundle\DTO\RequestTokenResponse
     */
    public $requestTokenResponse;

}
