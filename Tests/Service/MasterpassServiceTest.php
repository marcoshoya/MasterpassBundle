<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Tests\BaseWebTestCase;
use Hoya\MasterpassBundle\Service\MasterpassService;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\Transaction;
use Hoya\MasterpassBundle\DTO\ExpressCheckout;

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
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doPairingData');
        
        // creates services
        $service = new MasterpassService($connector);
        $response = $service->getPairingData($callback, $userid);
        
        $this->assertRegExp('/pairingId/', $response, 'Response does not contain accountNumber');
        
        return $response;
    }
    
    /**
     * Test payment data.
     * 
     * @depends testPairing
     */
    public function testPrecheckoutData($pairingResponse)
    {
        $stub = <<<JSON
{
  "cards" : [ {
    "brandName" : "MasterCard",
    "cardHolderName" : "James Stone",
    "cardId" : "6243e3bf-ea2a-4cbc-bec7-323684d5ee38",
    "expiryYear" : 2021,
    "expiryMonth" : 11,
    "lastFour" : "0014"
  }, {
    "brandName" : "Visa",
    "cardHolderName" : "James Stone",
    "cardId" : "2f6074b8-7f1e-4ac0-a1d0-f4679cac7f79",
    "expiryYear" : 2023,
    "expiryMonth" : 12,
    "lastFour" : "1111"
  } ],
  "shippingAddresses" : [ {
    "recipientInfo" : {
      "recipientName" : "James Stone",
      "recipientPhone" : "9171234567"
    },
    "addressId" : "45910e9f-4887-48fc-9a79-0b39428e89e0",
    "city" : "New York",
    "country" : "US",
    "subdivision" : "US-NY",
    "line1" : "123 Main St",
    "postalCode" : "10011"
  }, {
    "recipientInfo" : {
      "recipientName" : "James Stone",
      "recipientPhone" : "9171234567"
    },
    "addressId" : "122fcc06-4b1d-4520-b191-e9f8577b1a8a",
    "city" : "Boston",
    "country" : "US",
    "subdivision" : "US-MA",
    "line1" : "323 Grand Ave",
    "postalCode" : "02125"
  } ],
  "contactInfo" : {
    "firstName" : "James",
    "lastName" : "Stone",
    "country" : "US",
    "emailAddress" : "james.stome@myemail.com",
    "phoneNumber" : "9171234567"
  },
  "preCheckoutTransactionId" : "dcdb243d-fbae-47fe-b59b-705e94242d2f",
  "consumerWalletId" : "30729ad10cf32ccde82e61938d79b195",
  "walletName" : "masterpass",
  "pairingId" : "be90edd54aaf343770e790679a23d66e8a615503"
}
JSON;
        
        $pairing = json_decode($pairingResponse);
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doPrecheckoutData');
        
        // creates services
        $service = new MasterpassService($connector);
        $response = $service->getPrecheckoutData($pairing->pairingId);
        
        $this->assertRegExp('/cards/', $response, 'Response does not contain cards');
        $this->assertRegExp('/pairingId/', $response, 'Response does not contain cards');
        
        return $response;
    }
    
    /**
     * Test payment data.
     * 
     * @depends testPrecheckoutData
     */
    public function testExpressCheckout($precheckoutData)
    {
        $data = json_decode($precheckoutData);
        $stub = <<<JSON
{
  "card" : {
    "brandId" : "master",
    "brandName" : "MasterCard",
    "accountNumber" : "************0014",
    "cardHolderName" : "James Stone",
    "expiryMonth" : 11,
    "expiryYear" : 2021,
    "billingAddress" : {
      "city" : "New York",
      "country" : "US",
      "subdivision" : "US-NY",
      "line1" : "123 Main St",
      "postalCode" : "10011"
    }
  },
  "shippingAddress" : {
    "city" : "New York",
    "country" : "US",
    "subdivision" : "US-NY",
    "line1" : "123 Main St",
    "postalCode" : "10011"
  },
  "personalInfo" : {
    "recipientName" : "James Stone",
    "recipientPhone" : "9171234567",
    "recipientEmailAddress" : "james.stone@myemail.com"
  },
  "walletId" : "101",
  "authenticationOptions" : {
    "authenticateMethod" : "NO AUTHENTICATION"
  },
  "pairingId" : "2a185727a8dd79a2ada8944cc19b0d38abcf2f57"
}
JSON;
        
        $expressCheckout = new ExpressCheckout();
        $expressCheckout->checkoutId = "a4a6x1ywxlkxzhensyvad1hepuouaesuv";
        $expressCheckout->pairingId = $data->pairingId;
        $expressCheckout->preCheckoutTransactionId = $data->preCheckoutTransactionId;
        $expressCheckout->setAmount("1.00");
        $expressCheckout->currency = "USD";
        $expressCheckout->cardId = "12345";
        $expressCheckout->shippingAddressId = "67890";
        $expressCheckout->digitalGoods = false;
        
        // mocks
        $connector = $this->getMockConnector($stub, 'doExpressCheckout');
        
        // creates services
        $service = new MasterpassService($connector);
        $response = $service->postExpressCheckout($expressCheckout);
        
        $this->assertRegExp('/card/', $response, 'Response does not contain cards');
        $this->assertRegExp('/pairingId/', $response, 'Response does not contain cards');
    }
   
}
