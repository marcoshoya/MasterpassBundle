<?php

namespace Hoya\MasterpassBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Hoya\MasterpassBundle\DTO\Shoppingcart;
use Hoya\MasterpassBundle\DTO\ShoppingcartItem;
use Hoya\MasterpassBundle\DTO\Transaction;
use Hoya\MasterpassBundle\Helper\MasterpassHelper;

/**
 * Masterpass Controller SDK
 */
class MasterpassController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $service = $this->get('hoya_masterpass_service');
        
        return $this->render('default/index.html.twig', array(
            'consumerKey' => $service->getConnector()->getConsumerKey(),
            'checkoutIdentifier' => $service->getCheckoutId()
        ));
    }

    /**
     * @Route("/requestToken", name="request_token")
     */
    public function requestTokenAction()
    {
        $service = $this->get('hoya_masterpass_service');
        $url = $this->get('hoya_masterpass_url');

        $requestToken = $service->getRequestToken();
        $this->get('session')->set('requestToken', $requestToken);

        $errorMessage = null;
        if (!$requestToken instanceof \Hoya\MasterpassBundle\DTO\RequestTokenResponse) {
            $errorMessage = 'error';
        }

        return $this->render('default/requestToken.html.twig', array(
                    'authHeader' => $service->getConnector()->authHeader,
                    'signatureBaseString' => $service->getConnector()->signatureBaseString,
                    'requestUrl' => $url->getRequestUrl(),
                    'requestToken' => $requestToken->requestToken,
                    'authorizeUrl' => $requestToken->authorizeUrl,
                    'oAuthExpiresIn' => $requestToken->oAuthExpiresIn,
                    'oAuthSecret' => $requestToken->oAuthSecret,
                    'errorMessage' => $errorMessage
        ));
    }

    /**
     * @Route("/shoppingCart", name="shopping_cart")
     */
    public function shoppingCartAction()
    {
        $service = $this->get('hoya_masterpass_service');
        $url = $this->get('hoya_masterpass_url');

        $render = [
            'authHeader' => null,
            'signatureBaseString' => null,
            'shoppingCartRequest' => null,
            'shoppingCartUrl' => null,
            'errorMessage' => null,
            'shoppingCartResponse' => null,
            'lightboxUrl' => $url->getLightboxUrl(),
            //'callbackUrl' => $url->getCallbackUrl(),
            'callbackUrl' => $this->generateUrl('callback', array(), true),
            'checkoutIdentifier' => $this->getParameter('hoya_masterpass.checkoutidentifier')
        ];

        $requestToken = $this->get('session')->get('requestToken');
        if (!$requestToken instanceof \Hoya\MasterpassBundle\DTO\RequestTokenResponse) {
            $render['errorMessage'] = 'Request token missing';
        } else {

            $item = new ShoppingcartItem();
            $item->quantity = 1;
            $item->imageUrl = 'https://localhost.com';
            $item->description = 'My test item';
            $item->setAmount(10.00);

            $cart = new Shoppingcart();
            $cart->currency = 'USD';
            $cart->addItem(1, $item);

            $shoppingCartXml = $cart->toXML();

            $shoppingCart = $service->postShoppingCartData($requestToken, $shoppingCartXml);
            if ($service->hasError()) {
                $render['errorMessage'] = $service->getErrorMessage();
            }

            $render['authHeader'] = $service->getConnector()->authHeader;
            $render['signatureBaseString'] = $service->getConnector()->signatureBaseString;
            $render['shoppingCartRequest'] = MasterpassHelper::formatXML($shoppingCartXml);
            $render['shoppingCartUrl'] = $url->getShoppingcartUrl();
            $render['shoppingCartResponse'] = MasterpassHelper::formatXML($shoppingCart);
            $render['requestToken'] = $requestToken->requestToken;
        }

        return $this->render('default/shoppingCart.html.twig', $render);
    }

    /**
     * @Route("/callback", name="callback")
     */
    public function callbackAction(Request $request)
    {
        $service = $this->get('hoya_masterpass_service');
        $callback = $service->handleCallback($request);

        $this->get('session')->set('callback', $callback);

        return $this->render('default/callback.html.twig', array(
                    'requestToken' => $callback->requestToken,
                    'requestVerifier' => $callback->requestVerifier,
                    'checkoutResourceUrl' => $callback->checkoutResourceUrl,
        ));
    }

    /**
     * @Route("/accessToken", name="access_token")
     */
    public function accessTokenAction()
    {
        $errorMessage = null;
        $callback = $this->get('session')->get('callback');
        if (!$callback instanceof \Hoya\MasterpassBundle\DTO\CallbackResponse) {
            throw new \Exception('Callback is not instance of CallbackResponse');
        }

        $service = $this->get('hoya_masterpass_service');
        $accessToken = $service->getAccessToken($callback);
        if ($service->hasError()) {
            $errorMessage = $service->getErrorMessage();
        }

        $this->get('session')->set('accessToken', $accessToken);
        $url = $this->get('hoya_masterpass_url');

        return $this->render('default/accessToken.html.twig', array(
                    'errorMessage' => $errorMessage,
                    'authHeader' => $service->getConnector()->authHeader,
                    'signatureBaseString' => $service->getConnector()->signatureBaseString,
                    'accessUrl' => $url->getAccessUrl(),
                    'accessToken' => $accessToken->accessToken,
                    'oAuthSecret' => $accessToken->oAuthSecret
        ));
    }

    /**
     * @Route("/processCheckout", name="process_checkout")
     */
    public function processCheckoutAction()
    {
        $errorMessage = null;
        $accessToken = $this->get('session')->get('accessToken');
        if (!$accessToken instanceof \Hoya\MasterpassBundle\DTO\AccessTokenResponse) {
            throw new \Exception('accessToken is not instance of AccessTokenResponse');
        }

        $service = $this->get('hoya_masterpass_service');
        $checkoutData = $service->getCheckoutData($accessToken);
        if ($service->hasError()) {
            $errorMessage = $service->getErrorMessage();
        }

        $this->get('session')->set('checkoutData', $checkoutData);

        return $this->render('default/checkoutData.html.twig', array(
                    'errorMessage' => $errorMessage,
                    'authHeader' => $service->getConnector()->authHeader,
                    'signatureBaseString' => $service->getConnector()->signatureBaseString,
                    'checkoutResourceUrl' => $accessToken->checkoutResourceUrl,
                    'checkoutData' => MasterpassHelper::formatXML($checkoutData)
        ));
    }

    /**
     * @Route("/transaction", name="transaction")
     */
    public function transactionAction()
    {
        $errorMessage = null;

        $url = $this->get('hoya_masterpass_url');
        $service = $this->get('hoya_masterpass_service');
        
        $purchase = new \DateTime;
        $transaction = new Transaction;
        $transaction->transactionId = 449087232;
        $transaction->consumerKey = $service->getConnector()->getConsumerKey();
        $transaction->currency = 'USD';
        $transaction->setAmount(1.00);
        $transaction->transactionStatus = 'Success';
        $transaction->setPurchaseDate($purchase);
        $transaction->approvalCode = 'sample';
        
        $transactionResponse = $service->postTransaction($transaction);
        if ($service->hasError()) {
            $errorMessage = $service->getErrorMessage();
        }

        return $this->render('default/transaction.html.twig', array(
                    'errorMessage' => $errorMessage,
                    'authHeader' => $service->getConnector()->authHeader,
                    'signatureBaseString' => $service->getConnector()->signatureBaseString,
                    'postbackUrl' => $url->getTransactionUrl(),
                    'postTransactionRequest' => MasterpassHelper::formatXML($transaction->toXML()),
                    'postTransactionResponse' => MasterpassHelper::formatXML($transactionResponse),
        ));
    }
}