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
                ->with($this->equalTo('oauth_verifier'))
                ->will($this->returnValue('2bddea1e3d84cfaf5a97b6dc6aa71258b3b96956'));
        $mock
                ->expects($this->at(2))
                ->method('get')
                ->with($this->equalTo('oauth_token'))
                ->will($this->returnValue('d84b9df1166070bc1abd484b783fd3b34a12f8cc'));
        
        $mock
                ->expects($this->at(3))
                ->method('get')
                ->with($this->equalTo('pairing_verifier'))
                ->will($this->returnValue('b64e1c85f4a3eafb13f5748ce09b48c90489471c'));
        
        $mock
                ->expects($this->at(4))
                ->method('get')
                ->with($this->equalTo('pairing_token'))
                ->will($this->returnValue('b64e1c85f4a3eafb13f5748ce09b48c90489471c'));

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
        $stub = '{"encryptedPaymentData":"eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiUlNBLU9BRVAifQ.jcE058JMIJ42Cepa6ctKgbidlIKNzDdCt1S4Qq-mNfITVic0x1-9rJ9AWx_lCtS8-_EHv-gEfsHi8hBQFkkv5atjILQXyyJbrVzQCyMRZokVoAnI5lrgSafaa2vLXpB5-eZRuhAH_p_VvSMfyhOvSVg2YR7SdBzKRiQhKSWMAzMgj1d-XkNo4gEc49pGqiuQCDjbN7Hd0vOJxmfMRJMobvoN5e7wyBL1FI9q1Rqa2NhWWJZp0Nu9_nyj9jFCjCTfAZm8CGqrbZV2IuQlH3i1FNNXxayWowPOCBf6mzm8q3iUlvWlR--pHnwcVrfH2y2Qhf42lC2JQgqCOIF4pCs1cg.k46vG798e3-8T2CY6RvYsQ.HjgqZW6c08t8CgqihN5akLcWpjnXDZpWw_LP3n7JiugzrMSqYFDgjJFwCbgfvhM64SBhZnytEltXrn5p3E_iFQZg2hZUFqQ9Mftpu5hcpw35AzFOsuuROgqbuz0JVxRWp-H2b7_hFMfyJnmrUgZ4xW1901zxiukgH3rc5zegamBVnes5VUqE2N-MqYCFc9IEC7H55CsZyt0mi1URbDvBFeu-K9_J5XvB0lMeDxgzqUjFh2rsi9P_-AzW8dvlRPxKOqDQYivbcChdMdlZ24BJXShLEJb-qsH1z43z7SI_N2yJCnApVq-y6252kUV_-EZWxEPQ66059nFnfuxH5RdkcjHD68L51suo29guRaB2TEE7lom8JsmJEHch_12WKLr4jlf2wBL_RjnQQx1yLVRLMQtA7KVS5U4LgmTeoul8yLBIaIJpU1YuTcKBRkGxUxh2wBb5tYIkX09ogkgRx2imzAKp4IUjDL1YilJ6EfFB5O5j6hetzuFcaixqB1novC1qQJyz1d6VQczJzgaPCNCyT4pDotXOcxzD9e-0uUwVhXWQPecqJpla25q6I1yn7lBNOCvghntJyK-7V0-nLvwxwq3d3y8eUvQaqncL5AVAceRWUstQFSvkJKIBWC6qKzVfjTEODFz7rBqUBMG4WWoWSDgGk_coSA-SIp4KY-frpvtl73zMMqHepgokPb8CXHhyi6wkRKSyPeAqozSUzWqWcyr2g1liBOBhcrUBjOwSmlzng7_J-Mg4TuudlQ7Lx0MCwIzDiV7LLDu0wH9rxKksCVy6VmvznvVUnHbpByW1ZTfKZPKeK6j7bJP543H_7AG409JP_FBp6WnXzc1KM5cwFg.O7OoU_Z3eXcDgiyNXfulmw"}';
        
        $cartid = '2d6896c0-2aa3-4e86-b41a-905a987b4734';
        $checkoutid = 'a4a6x1ywxlkxzhensyvad1hepuouaesuv';
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doEncryptedData');
        $brand = new \Hoya\MasterpassBundle\Common\Brand($checkoutid);
        
        // creates services
        $service = new MasterpassService($connector, $brand);
        $response = $service->getEncryptedData($callback, $cartid);
        
        $this->assertRegExp('/encryptedPaymentData/', $response, 'Response does not contain accountNumber');
        
        return $response;
    }
    
    /**
     * Test decryption with JOSE dependency
     * 
     * @depends testEncryptedData
     */
    public function testDecryptResponse($response)
    {
        $payload = $this->getService()->decryptResponse($response);

        $this->assertRegExp('/accountNumber/', $payload, 'Response does not contain accountNumber');
    }
    
    /**
     * Test payment data.
     * 
     * @depends testCallback
     */
    public function testPairing(CallbackResponse $callback)
    {
        $stub = '{"pairingId" : "03787bf8cd09e22c3185423a6998abd27069a881"}';
        $userid = 'joe.test@example.com';
        $checkoutid = 'a4a6x1ywxlkxzhensyvad1hepuouaesuv';
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doPairingData');
        $brand = new \Hoya\MasterpassBundle\Common\Brand($checkoutid);
        
        // creates services
        $service = new MasterpassService($connector, $brand);
        $response = $service->getPairingData($callback, $userid);
        
        $this->assertRegExp('/pairingId/', $response, 'Response does not contain accountNumber');
    }
    
    
   
}
