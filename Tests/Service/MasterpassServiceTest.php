<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Common\Connector;
use Hoya\MasterpassBundle\Tests\BaseWebTestCase;
use Hoya\MasterpassBundle\DTO\RequestTokenResponse;
use Hoya\MasterpassBundle\DTO\Shoppingcart;
use Hoya\MasterpassBundle\DTO\ShoppingcartItem;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\AccessTokenResponse;
use Hoya\MasterpassBundle\Service\MasterpassService;

/**
 * MasterpassServiceTest.
 *
 * @author Marcos Lazarin
 */
class MasterpassServiceTest extends BaseWebTestCase
{

    public $checkout = '<Checkout><Card><BrandId>master</BrandId><BrandName>MasterCard</BrandName><AccountNumber>5555555555554444</AccountNumber><BillingAddress><City>Boca Raton</City><Country>US</Country><CountrySubdivision>US-FL</CountrySubdivision><Line1>6600 Mobile Site Street</Line1><Line2>421</Line2><Line3></Line3><PostalCode>33496</PostalCode></BillingAddress><CardHolderName>JOE Test</CardHolderName><ExpiryMonth>4</ExpiryMonth><ExpiryYear>2017</ExpiryYear></Card><TransactionId>434801298</TransactionId><Contact><FirstName>JOE</FirstName><LastName>Test</LastName><Country>US</Country><EmailAddress>joe.test@email.com</EmailAddress><PhoneNumber>1-9876543210</PhoneNumber></Contact><ShippingAddress><City>New York</City><Country>SE</Country><CountrySubdivision>US-NY</CountrySubdivision><Line1>100 Street</Line1><Line2>Apt 6D</Line2><Line3></Line3><PostalCode>10128</PostalCode><RecipientName>JOE Test</RecipientName><RecipientPhoneNumber>US+1-12345</RecipientPhoneNumber></ShippingAddress><WalletID>101</WalletID><PreCheckoutTransactionId>a4a6x55-f2oib5-ik9vzomt-1-ikyc8085-m444</PreCheckoutTransactionId></Checkout>';

    /**
     * @return MasterpassService
     */
    protected function getService()
    {
        return $this->getContainer()->get('hoya_masterpass_service');
    }

    /**
     * Testing service instance.
     */
    public function testInstance()
    {
        $this->assertInstanceOf('\Hoya\MasterpassBundle\Service\MasterpassService', $this->getService());
    }

    /**
     * Testing request token.
     * 
     * @return RequestTokenResponse
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
     * Testing shoppingcart.
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

    public function testHandleCallback()
    {
        //oauth_token=500f3cab67f49658f106e7601a0b06246c97b5be&oauth_verifier=d3d5da6c404b3e76a0104ae8398d7b5bc76b266b&checkoutId=446152630&checkout_resource_url=https%3A%2F%2Fsandbox.api.mastercard.com%2Fmasterpass%2Fv6%2Fcheckout%2F446152630&mpstatus=success
    }

    /**
     * Test access token return.
     */
    public function testAccessToken()
    {
        $return = 'oauth_token=c7d33d2c6b6b49dc17db786c73a73b3abcadc43a&oauth_token_secret=399e50ba507a0faa27300ecfb50d55390f51f539';

        $connector = $this->getMockConnector($return);

        $service = new MasterpassService($connector);

        $callback = new CallbackResponse;
        $callback->requestVerifier = '8f55989fa03a6dbe173749d0c495872f4a38d84c';
        $callback->requestToken = '259f063894e0a1ab8996f805bbbeeab535812d6f';

        $accessTokenResponse = $service->getAccessToken($callback);
        $this->assertInstanceOf('\Hoya\MasterpassBundle\DTO\AccessTokenResponse', $accessTokenResponse);
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
        $connector = $this->getMockConnector($return);
        $service = new MasterpassService($connector);
        
        $accessToken = new AccessTokenResponse;
        $accessToken->checkoutResourceUrl = 'https://sandbox.api.mastercard.com/masterpass/v6/checkout/446156154';
        $accessToken->accessToken = 'c7d33d2c6b6b49dc17db786c73a73b3abcadc43a';
        
        $checkoutData = $service->getCheckoutData($accessToken);

        //$this->assertRegExp('<Checkout>', $checkoutData, 'Response does not contain Checkout');
        //$this->assertRegExp('<TransactionId>', $checkoutData, 'Response does not contain TransactionId');
    }

    /**
     * Mock connector service.
     * 
     * @param string $return
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connector
     */
    protected function getMockConnector($return)
    {
        $mock = $this->getMockBuilder('Hoya\MasterpassBundle\Common\Connector')
                ->disableOriginalConstructor()
                ->getMock();

        $mock->expects($this->any())
                ->method('connect')
                ->will($this->returnValue($return));

        return $mock;
    }

}
