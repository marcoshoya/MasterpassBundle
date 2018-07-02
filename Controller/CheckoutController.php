<?php

namespace Hoya\MasterpassBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Hoya\MasterpassBundle\DTO\CallbackResponse;
use Hoya\MasterpassBundle\DTO\Transaction;
use Hoya\MasterpassBundle\DTO\ExpressCheckout;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CheckoutController extends Controller {

    protected $cartid = "2d6896c0-2aa3-4e86-b41a-905a987b4734";
    protected $userId = "joe.doe@example.com";

    /**
     * @Route("/checkout", name="checkout")
     */
    public function standardAction()
    {
        $service = $this->get('hoya_masterpass_service');

        $template = [
            'checkoutId' => $service->getCheckoutId(),
            'cartId' => $this->cartid,
            'userId' => "joe.doe@example.com",
            'callbackUrl' => null
        ];

        // replace this example code with whatever you need
        return $this->render('checkout/standard.html.twig', $template);
    }

    /**
     * @Route("/callback", name="callback")
     */
    public function callbackAction(Request $request)
    {
        $callback = $this->get('hoya_masterpass_service')->handleCallback($request);
        $this->get('session')->set('callback', $callback);

        $pairing = $callback->pairingVerifier ? true : false;
        $cancel = $callback->mpstatus == 'success' ? false : true;

        return $this->render('checkout/callback.html.twig', ['callback' => $callback, 'pairing' => $pairing, 'cancel' => $cancel]);
    }

    /**
     * @Route("/payment", name="payment")
     */
    public function paymentAction()
    {
        //$callback = $this->get('hoya_masterpass_service')->handleCallback($request);

        $callback = $this->get('session')->get('callback');
        if (!$callback instanceof CallbackResponse) {
            throw new \Exception("CallbackResponse not found");
        }

        $payment = $this->get('session')->get('payment');
        if (!$payment) {
            try {
                
                $payment = $this->get('hoya_masterpass_service')->getPaymentData($callback, $this->cartid);
                $this->get('session')->set('payment', $payment);
                
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }

        $template = [
            'tid' => $callback->oauthToken,
            'payment' => $payment,
            'decrypted' => null,
            'pairing' => false
        ];

        return $this->render('checkout/payment.html.twig', $template);
    }

    /**
     * @Route("/encrypted", name="encrypted")
     */
    public function encryptedAction()
    {
        $callback = $this->get('session')->get('callback');
        if (!$callback instanceof CallbackResponse) {
            throw new \Exception("CallbackResponse not found");
        }

        $payment = $this->get('session')->get('encrypted');
        if (!$payment) {
            try {
                
                $payment = $this->get('hoya_masterpass_service')->getEncryptedData($callback, $this->cartid);
                $this->get('session')->set('encrypted', $payment);
                
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }

        $decrypted = $this->get('hoya_masterpass_service')->decryptResponse($payment);

        $template = [
            'tid' => $callback->oauthToken,
            'payment' => $payment,
            'decrypted' => $decrypted,
            'pairing' => false
        ];

        return $this->render('checkout/payment.html.twig', $template);
    }

    /**
     * @Route("/postback", name="postback")
     */
    public function postbackAction()
    {
        $callback = $this->get('session')->get('callback');
        
        // new postback
        $tr = new Transaction();
        
        // check if it is pairing flow
        if ($callback instanceof CallbackResponse) {
                $tr->transactionId = $callback->oauthToken;
        } else {
            $precheckout = $this->get('session')->get('precheckout', null);
            $tr->transactionId = $precheckout['preCheckoutTransactionId'];
            $tr->preCheckoutTransactionId = $precheckout['preCheckoutTransactionId'];
        }

        $postback = $this->get('session')->get('postback', null);

        if (!$postback) {
            try {
                $purchase = new \DateTime;

                $tr->currency = 'USD';
                $tr->setAmount('1.00');
                $tr->paymentSuccessful = true;
                $tr->paymentCode = 'sample';
                $tr->setPurchaseDate($purchase);

                $this->get('hoya_masterpass_service')->postTransaction($tr);
                $this->get('session')->getFlashBag()->add('success', 'Postback was sent successfully');

                $this->get('session')->set('postback', $tr);
                
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }

        $this->get('session')->remove('encrypted');
        $this->get('session')->remove('payment');
        $this->get('session')->remove('callback');
        $this->get('session')->remove('express');

        return $this->render('checkout/postback.html.twig', ['tid' => $tr->transactionId]);
    }

    /**
     * @Route("/pairing", name="pairing")
     */
    public function pairingAction()
    {
        $callback = $this->get('session')->get('callback');
        if (!$callback instanceof CallbackResponse) {
            throw new \Exception("CallbackResponse not found");
        }

        $pairing = $this->get('session')->get('pairing');
        if (!$pairing) {
            $pairing = $this->get('hoya_masterpass_service')->getPairingData($callback, $this->userId);
            $this->get('session')->set('pairing', $pairing);
        }

        $template = [
            'tid' => $callback->oauthToken,
            'payment' => $pairing,
            'decrypted' => null,
            'pairing' => true
        ];

        return $this->render('checkout/payment.html.twig', $template);
    }
    
    /**
     * @Route("/precheckout", name="precheckout")
     */
    public function precheckoutAction(Request $request)
    {
        $service = $this->get('hoya_masterpass_service');
        
        $pairing = $this->get('session')->get('pairing', null);
        if ($pairing === null) {
            $this->get('session')->getFlashBag()->add('error', 'PairingId not found');
            return $this->redirectToRoute('homepage');
        }
        
        try {
            
            $pairing = json_decode($pairing);
            $getPrecheckoutData = $service->getPrecheckoutData($pairing->pairingId);        
            $preCheckoutData = json_decode($getPrecheckoutData);
            
            if ($preCheckoutData) {
                $pairing = json_encode(['pairingId' => $preCheckoutData->pairingId]);
                // persists new pairing
                $this->get('session')->set('pairing', $pairing);
            }
                        
        } catch (\Exception $ex) {
            $this->get('session')->getFlashBag()->add('error', $ex->getMessage());
            $this->get('session')->remove('pairing');
            
            return $this->redirectToRoute('homepage');
        }
        
        $form = $this->buildForm($preCheckoutData);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->get('session')->set('precheckout', $data);
            
            return $this->redirectToRoute('express');
        }        
        
        $template = [
            'checkoutId' => $service->getCheckoutId(),
            'cartId' => $this->cartid,
            'userId' => "joe.doe@example.com",
            'callbackUrl' => null,
            'form' => $form->createView()
        ];

        // replace this example code with whatever you need
        return $this->render('checkout/precheckout.html.twig', $template);
    }
    
    /**
     * Build form
     * 
     * @param stdClass $preCheckoutData
     * @return Form
     */
    private function buildForm($preCheckoutData)
    {
        $cards = $ships = [];
        foreach ($preCheckoutData->cards as $card) {
            $str = sprintf('xxxx xxxx xxxx %s | %s', $card->lastFour, $card->brandName);
            $cards[$str] = $card->cardId;
        }
        
        foreach ($preCheckoutData->shippingAddresses as $ship) {
            $str = sprintf('%s | %s - %s', $ship->line1, $ship->city, $ship->country);
            $ships[$str] = $ship->addressId;
        }
        
        $form = $this->createFormBuilder([], [
            'action' => $this->generateUrl('precheckout'),
            'method' => 'POST',
        ])->getForm();
        
        $form
            ->add('cardId', ChoiceType::class, [
                'choices' => $cards,
            ])
            ->add('shippingId', ChoiceType::class, [
                'choices' => $ships,
            ])
            ->add('preCheckoutTransactionId', HiddenType::class, [
                'data' => $preCheckoutData->preCheckoutTransactionId
            ])
        ;
        
        return $form;
    }
    
    /**
     * @Route("/express", name="express")
     */
    public function expressAction(Request $request)
    {
        $service = $this->get('hoya_masterpass_service');
        
        $precheckout = $this->get('session')->get('precheckout', null);
        if ($precheckout === null) {
            $this->get('session')->getFlashBag()->add('error', 'PreCheckout Data not Found');
            
            return $this->redirectToRoute('homepage');
        }
        
        $express = $this->get('session')->get('express', null);
        if ($express === null) {

            $pairing = $this->get('session')->get('pairing', null);
            $pairing = json_decode($pairing);
            
            $expressCheckout = new ExpressCheckout();
            $expressCheckout->checkoutId = $service->getCheckoutId();
            $expressCheckout->pairingId = $pairing->pairingId;
            $expressCheckout->preCheckoutTransactionId = $precheckout['preCheckoutTransactionId'];
            $expressCheckout->setAmount("1.00");
            $expressCheckout->currency = "USD";
            $expressCheckout->cardId = $precheckout['cardId'];
            $expressCheckout->shippingAddressId = $precheckout['shippingId'];
            $expressCheckout->digitalGoods = false;

            $express = $service->postExpressCheckout($expressCheckout);
            $expressJson = json_decode($express);
            
            $this->get('session')->set('express', $express);
            
            // persists new pairing
            $pairing = json_encode(['pairingId' => $expressJson->pairingId]);
            $this->get('session')->set('pairing', $pairing);
        }
        
        $template = [
            'tid' => $precheckout['preCheckoutTransactionId'],
            'payment' => $express,
            'decrypted' => null,
            'pairing' => false
        ];

        // replace this example code with whatever you need
        return $this->render('checkout/payment.html.twig', $template);
    }

}
