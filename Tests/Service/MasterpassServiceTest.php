<?php

namespace Hoya\MasterpassBundle\Tests\Service;

use Hoya\MasterpassBundle\Tests\BaseWebTestCase;

/**
 * MasterpassServiceTest
 *
 * @author Marcos Lazarin
 */
class MasterpassServiceTest extends BaseWebTestCase
{
    public function testTest()
    {
        $container = $this->getContainer();
        
        $class = $container->get('hoya_masterpass_service');
        
        $this->assertInstanceOf('\Hoya\MasterpassBundle\Service\MasterpassService', $class);
    }

}
