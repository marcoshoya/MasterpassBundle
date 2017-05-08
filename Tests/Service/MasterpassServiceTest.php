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
    const ACCESSTOKEN = 'doAccessToken';
    
    const CHECKOUTDATA = 'doCheckoutData';
    
    const TRANSACTION = 'doTransaction';

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
     * Testing request token
     * 
     * @return \Hoya\MasterpassBundle\DTO\RequestTokenResponse
     */
    public function testRequestToken()
    {
        $rt = $this->getService()->getRequestToken();

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

        $cartXml = $this->getService()->postShoppingCartData($requestToken, $shoppingCartXml);

        $this->assertRegExp('<ShoppingCartResponse>', $cartXml, 'Response does not contain ShoppingCartResponse');
        $this->assertRegExp('<OAuthToken>', $cartXml, 'Response does not contain OAuthToken');

        return $cartXml;
    }
    
    /**
     * Testing shoppingcart
     * 
     * @depends testRequestToken
     */
    public function testMerchantInit(RequestTokenResponse $requestToken)
    {
        $init = new MerchantInit();
        $initXml = $init->toXML();
        
        $responseXml = $this->getService()->postMerchantInitData($requestToken, $initXml);

        $this->assertRegExp('<MerchantInitializationResponse>', $responseXml, 'Response does not contain MerchantInitializationResponse');
        $this->assertRegExp('<OAuthToken>', $responseXml, 'Response does not contain OAuthToken');

        return $initXml;
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
     * Test access token return.
     * 
     * @depends testCallback
     */
    public function testAccessToken(CallbackResponse $callback)
    {
        $return = 'oauth_token=c7d33d2c6b6b49dc17db786c73a73b3abcadc43a&oauth_token_secret=399e50ba507a0faa27300ecfb50d55390f51f539';

        $connector = $this->getMockConnector($return, self::ACCESSTOKEN);

        $service = new MasterpassService($connector);
        $accessTokenResponse = $service->getAccessToken($callback);

        $this->assertInstanceOf('\Hoya\MasterpassBundle\DTO\AccessTokenResponse', $accessTokenResponse);
        $this->assertEquals('c7d33d2c6b6b49dc17db786c73a73b3abcadc43a', $accessTokenResponse->accessToken, 'accessToken does not have a valid value');
    }

    public function testCheckoutData()
    {
        $return = <<<XML
<Checkout>
   <Card>
      <BrandId>master</BrandId>
      <BrandName>MasterCard</BrandName>
      <AccountNumber>5204740009900022</AccountNumber>
      <BillingAddress>
         <City>Milpitas</City>
         <Country>US</Country>
         <CountrySubdivision>US-CA</CountrySubdivision>
         <Line1>123 S Main St</Line1>
         <PostalCode>95035</PostalCode>
      </BillingAddress>
      <CardHolderName>Joe test</CardHolderName>
      <ExpiryMonth>1</ExpiryMonth>
      <ExpiryYear>2020</ExpiryYear>
   </Card>
   <TransactionId>446156154</TransactionId>
   <Contact>
      <FirstName>Joe</FirstName>
      <LastName>test</LastName>
      <Country>US</Country>
      <EmailAddress>joe.test@example.com</EmailAddress>
      <PhoneNumber>6547531792</PhoneNumber>
   </Contact>
   <ShippingAddress>
      <City>tuscaloosa</City>
      <Country>US</Country>
      <CountrySubdivision>US-AL</CountrySubdivision>
      <Line1>123 main street</Line1>
      <PostalCode>35404</PostalCode>
      <RecipientName>Joe test</RecipientName>
      <RecipientPhoneNumber>6547530000</RecipientPhoneNumber>
   </ShippingAddress>
   <WalletID>101</WalletID>
   <ExtensionPoint>
      <CardVerificationStatus>001</CardVerificationStatus>
   </ExtensionPoint>
</Checkout>
XML;
        $connector = $this->getMockConnector($return, self::CHECKOUTDATA);
        $service = new MasterpassService($connector);

        $accessToken = new AccessTokenResponse;
        $accessToken->checkoutResourceUrl = 'https://sandbox.api.mastercard.com/masterpass/v6/checkout/446156154';
        $accessToken->accessToken = 'c7d33d2c6b6b49dc17db786c73a73b3abcadc43a';

        $checkoutData = $service->getCheckoutData($accessToken);

        $this->assertRegExp('<Checkout>', $checkoutData, 'Response does not contain Checkout');
        $this->assertRegExp('<TransactionId>', $checkoutData, 'Response does not contain TransactionId');
    }

    public function testTransaction()
    {
        $return = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<MerchantTransactions>
   <MerchantTransactions>
      <TransactionId>449087232</TransactionId>
      <ConsumerKey>cLb0tKkEJhGTITp_6ltDIibO5Wgbx4rIldeXM_jRd4b0476c!414f4859446c4a366c726a327474695545332b353049303d</ConsumerKey>
      <Currency>USD</Currency>
      <OrderAmount>100</OrderAmount>
      <PurchaseDate>2016-07-27T05:31:45+02:00</PurchaseDate>
      <TransactionStatus>Success</TransactionStatus>
      <ApprovalCode>sample</ApprovalCode>
   </MerchantTransactions>
</MerchantTransactions>
XML;
        $purchase = new \DateTime;
        $transaction = new Transaction;
        $transaction->transactionId = 449087232;
        $transaction->consumerKey = 'cLb0tKkEJhGTITp_6ltDIibO5Wgbx4rIldeXM_jRd4b0476c!414f4859446c4a366c726a327474695545332b353049303d';
        $transaction->currency = 'USD';
        $transaction->setAmount(1.00);
        $transaction->transactionStatus = 'Success';
        $transaction->setPurchaseDate($purchase);
        $transaction->approvalCode = 'sample';

        $connector = $this->getMockConnector($return, self::TRANSACTION);
        $service = new MasterpassService($connector);

        $response = $service->postTransaction($transaction);
        $this->assertRegExp('<TransactionId>', $response, 'Response does not contain TransactionId');
    }

}
