<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hoya\MasterpassBundle\Tests\Functional\app;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * App Test Kernel for functional tests.
 */
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Hoya\MasterpassBundle\HoyaMasterpassBundle(),
            new \Hoya\MasterpassBundle\Tests\Functional\HoyaMasterpassTestBundle()
        );

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return __DIR__.'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return __DIR__.'/logs/';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/'.$this->environment.'.yml');

        /*
        // If symfony/framework-bundle > 3.0
        if (!class_exists('Symfony\Bundle\FrameworkBundle\Command\RouterApacheDumperCommand')) {
            $loader->load(__DIR__ . '/config/twig_assets.yml');
        }
        */
    }

    public function serialize()
    {
        return serialize(array($this->getEnvironment(), $this->isDebug()));
    }

    public function unserialize($str)
    {
        call_user_func_array(array($this, '__construct'), unserialize($str));
    }
}
