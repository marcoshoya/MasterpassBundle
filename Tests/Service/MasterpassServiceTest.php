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
    
    /**
     * Test payment data.
     * 
     * @depends testCallback
     */
    public function testEncryptedData(CallbackResponse $callback)
    {
        $stub = '{"encryptedPaymentData":"eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiUlNBLU9BRVAifQ.fWOubNQaP_-W41kGBdvchxw9zIJDZmt06SlN2VaLhgpFD7YChEh5U3-INInebUyg5L60YUwJVL80saNZL3t8HyXgcH2Y2v4_2qus6vejFsEo5fWg3cgfLB5b8pMygVdzeN7J7iPBmULlHirex25a7Bh256qqWaRBmZImABEBRcGfVKOs7OQuUX4NP8dI7FZdDnCh7sIjA5y0svcz4K1DzOCIgDuU5J1muw6QTAyX946wGL1pRFOEENXI8vYmeAtCmV83HR7psCNkzN2Evup69yXrtRgzuUzKUucAjOn3QoLyMx4PB7cIJBztRMnYj2IXZhBU23wxQkuhJS2WPh_oKA.LEKYrRhmSDw_s5vfjggCQQ.NdK9eEF26HLua8HgHjnkGYRQr_kgu3emGHNfe128qqgZbIVQLUjqbsPStHVhgHKzlKSEQEgI40Trz1jtSER9MwK3l1Z3hkwRB7coaRm65DnjpDJaldfyBCc9tIPE8rhoMe_dWN0QMd9QKeB21TbbopeCDDADJtqK8J59OWQ43gJMV2jrJpCme9rUdlrxSBy7PC38gpF6jbqnfEfKdipC_ncowNm9YUyjNHtI_a5mhvEK2DhqMXnVOlefJQoRKorihY0TxV5HcocX41sLtRkgUCnniMfZkRiwgQA0SumO0Nm-DAdhJTpAMyT4acyTk7J283avGKoZNtqkJlJlshBjuecQY6ivAru85wsAxZ0D8iHsSGtqJeiRCkvjvxe9f5fL1kxRnVDqU4iBTN_uJCw4kuWSkdf0PeYBMnFO5KqxT9eCCp8CnGwSg2GZLiPeW3bToMglP5h0NzBJRhJzqyjpSPiIy09cvGKmlh-YB3Lp3BpadK4aanLE2yDt7pmeRJEIWF1Oj6l7LM-dlUq1YRxxQdmBtf7BG6RL_XJMju1JhH3n-4GP9bT1LvdUGVegtjDTCGY_LTTfNlaAMpwlIuTuz6o_208bf7_OuB4uFp82LHW6uWwyZZuWc1SP303nZFDAheSiNfW-ur4j1x0XQZsAGUuFxfrXScqT5gvArzZzOpg.tyj-qGmo-zE5r_agPvFPwg"}';
        $cartid = '2d6896c0-2aa3-4e86-b41a-905a987b4734';
        $checkoutid = 'a4a6x1ywxlkxzhensyvad1hepuouaesuv';
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doEncryptedData');
        $brand = new \Hoya\MasterpassBundle\Common\Brand($checkoutid);
        
        // creates services
        $service = new MasterpassService($connector, $brand);
        $response = $service->getEncryptedData($callback, $cartid);
        
        $this->assertRegExp('/encryptedPaymentData/', $response, 'Response does not contain accountNumber');
    }
    
    
    
}
