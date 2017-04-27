<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * Brand class
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class Brand implements BrandInterface
{
    /**
     * @var integer id
     */
    private $id;
    
    /**
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }
    
    /**
     * Get checkout id
     * 
     * @return integer
     */
    public function getCheckoutId()
    {
        return $this->id;
    }
}
