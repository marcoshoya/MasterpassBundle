<?php

namespace Hoya\MasterpassBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Masterpass Controller SDK
 */
class MasterpassController extends Controller
{
    /**
     * Index action
     * 
     * @Route("/", name="masterpass_index")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
}