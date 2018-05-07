<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Tests\BaseWebTestCase;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;
use Hoya\MasterpassBundle\DTO\Shoppingcart;
use Hoya\MasterpassBundle\DTO\ShoppingcartItem;
use Hoya\MasterpassBundle\DTO\MerchantInit;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\AccessTokenResponse;
use Hoya\MasterpassBundle\DTO\Transaction;
use Hoya\MasterpassBundle\Service\MasterpassService;

/**
 * MasterpassServiceTest
 *
 * @author Marcos Lazarin
 * @group legacy
 */
class MasterpassServiceTest extends BaseWebTestCase
{
    /**
     * Invokes the service itself
     * 
     * @return \Hoya\MasterpassBundle\Service\MasterpassService
     */
    protected function getService()
    {
        return $this->getContainer()->get('hoya_masterpass_service');
    }

    /**
     * Testing service instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('\Hoya\MasterpassBundle\Service\MasterpassService', $this->getService());
    }

    /**
     * Testing callback service
     */
    public function testCallback()
    {
        // mock request
        $mock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
                ->disableOriginalConstructor()
                ->getMock();

        // starting mocking request parameters
        $mock
                ->expects($this->at(0))
                ->method('get')
                ->with($this->equalTo('mpstatus'))
                ->will($this->returnValue('success'));
        $mock
                ->expects($this->at(1))
                ->method('get')
                ->with($this->equalTo('checkout_resource_url'))
                ->will($this->returnValue('https%3A%2F%2Fsandbox.api.mastercard.com%2Fmasterpass%2Fv6%2Fcheckout%2F435527236'));
        $mock
                ->expects($this->at(2))
                ->method('get')
                ->with($this->equalTo('oauth_verifier'))
                ->will($this->returnValue('2bddea1e3d84cfaf5a97b6dc6aa71258b3b96956'));
        $mock
                ->expects($this->at(3))
                ->method('get')
                ->with($this->equalTo('oauth_token'))
                ->will($this->returnValue('d84b9df1166070bc1abd484b783fd3b34a12f8cc'));

        $callback = $this->getService()->handleCallback($mock);

        $this->assertInstanceOf('\Hoya\MasterpassBundle\DTO\CallbackResponse', $callback);
        $this->assertEquals('success', $callback->mpstatus);
        $this->assertEquals('2bddea1e3d84cfaf5a97b6dc6aa71258b3b96956', $callback->oauthVerifier);
        
        return $callback;
    }
    
}
