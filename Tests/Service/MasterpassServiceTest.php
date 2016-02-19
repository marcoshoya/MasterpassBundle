<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Tests\BaseWebTestCase;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;
use Hoya\MasterpassBundle\DTO\Shoppingcart;
use Hoya\MasterpassBundle\DTO\ShoppingcartItem;

/**
 * MasterpassServiceTest
 *
 * @author Marcos Lazarin
 */
class MasterpassServiceTest extends BaseWebTestCase
{
    /**
     * @var \Hoya\MasterpassBundle\Service\MasterpassService
     */
    protected $service;
    
    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();
        
        $container = $this->getContainer();
        $this->service = $container->get('hoya_masterpass_service');
    }
    
    /**
     * Testing service instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('\Hoya\MasterpassBundle\Service\MasterpassService', $this->service);
    }
    
    /**
     * Testing request token
     * 
     * @return \Hoya\MasterpassBundle\DTO\RequestTokenResponse
     */
    public function testRequestToken()
    {
        $rt = $this->service->getRequestToken();

        $this->assertInstanceOf('\Hoya\MasterpassBundle\DTO\RequestTokenResponse', $rt);
        $this->assertGreaterThanOrEqual(40, strlen($rt->requestToken), 'requestToken does not have a valid format');
        $this->assertGreaterThanOrEqual(40, strlen($rt->oAuthSecret), 'oAuthSecret does not have a valid format');
        $this->assertEquals(900, $rt->oAuthExpiresIn, 'oAuthExpiresIn does not have a valid value');
        
        return $rt;
    }
    
    /**
     * Testing shoppingcart
     * 
     * @depends testRequestToken
     */
    public function testShoppingCart(RequestTokenResponse $requestToken)
    {
        $item = new ShoppingcartItem();
        $item->quantity = 1;
        $item->imageUrl = 'https://localhost.com';
        $item->description = 'My test item';
        $item->setAmount(10.00);
        
        $cart = new Shoppingcart();
        $cart->currency = 'USD';
        $cart->addItem(1, $item);
        
        $shoppingCartXml = $cart->toXML();
        
        $cart = $this->service->postShoppingCartData($requestToken, $shoppingCartXml);
        
        $this->assertRegExp('<ShoppingCartResponse>', $cart, 'Response does not contain ShoppingCartResponse');
        $this->assertRegExp('<OAuthToken>', $cart, 'Response does not contain OAuthToken');        
    }

}
