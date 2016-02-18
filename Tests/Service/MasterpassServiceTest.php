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
    /**
     * @var \Hoya\MasterpassBundle\Service\MasterpassService
     */
    protected $service;
    
    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();
        
        $container = $this->getContainer();
        $this->service = $container->get('hoya_masterpass_service');
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf('\Hoya\MasterpassBundle\Service\MasterpassService', $this->service);
    }
    
    public function testRequestToken()
    {
        $rt = $this->service->getRequestToken();

        $this->assertInstanceOf('\Hoya\MasterpassBundle\DTO\RequestTokenResponse', $rt);
        $this->assertGreaterThanOrEqual(40, strlen($rt->requestToken), 'requestToken does not have a valid format');
        $this->assertGreaterThanOrEqual(40, strlen($rt->oAuthSecret), 'oAuthSecret does not have a valid format');
        $this->assertEquals(900, $rt->oAuthExpiresIn, 'oAuthExpiresIn does not have a valid value');
    }

}
