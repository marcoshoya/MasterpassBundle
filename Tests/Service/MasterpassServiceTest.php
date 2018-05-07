<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Tests\BaseWebTestCase;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\Service\MasterpassService;
use Hoya\MasterpassBundle\DTO\Transaction;

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
    
    /**
     * Test payment data.
     * 
     * @depends testCallback
     */
    public function testPaymentData(CallbackResponse $callback)
    {
        $stub = '{"card":{"brandId":"master","brandName":"MasterCard","accountNumber":"5506900140100305","billingAddress":{"city":"O\'Fallon","country":"US","subdivision":"US-MO","line1":"2200 MasterCard Boulevard","postalCode":"63368"},"cardHolderName":"Hoya spt","expiryMonth":10,"expiryYear":2020},"shippingAddress":{"city":"O\'Fallon","country":"US","subdivision":"US-MO","line1":"2200 MasterCard Boulevard","postalCode":"63368"},"personalInfo":{"recipientName":"Hoya spt","recipientPhone":"1234987655","recipientEmailAddress":"joedoe@example.com"},"walletId":"101","authenticationOptions":{"authenticateMethod":"NO AUTHENTICATION"}}';
        $cartid = '2d6896c0-2aa3-4e86-b41a-905a987b4734';
        $checkoutid = 'a4a6x1ywxlkxzhensyvad1hepuouaesuv';
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doPaymentData');
        $brand = new \Hoya\MasterpassBundle\Common\Brand($checkoutid);
        
        // creates services
        $service = new MasterpassService($connector, $brand);
        $response = $service->getPaymentData($callback, $cartid);
        
        $this->assertRegExp('/accountNumber/', $response, 'Response does not contain accountNumber');
    }
    
    /**
     * Test postback api
     */
    public function testPostback()
    {
        $purchase = new \DateTime;
         
        $tr = new Transaction();

        $tr->transactionId = 'feae1cae49b93bc0fbffcb462d6d3da8056ae6eb';
        $tr->currency = 'USD';
        $tr->setAmount('1.00');
        $tr->paymentSuccessful = true;
        $tr->paymentCode = 'sample';
        $tr->setPurchaseDate($purchase);
        
        // mocks
        $connector = $this->getMockConnector(null, 'doTransaction');
        
        $service = new MasterpassService($connector);
        $response = $service->postTransaction($tr);
        
        $this->assertNull($response, 'Response is not null');
    }
    
}
