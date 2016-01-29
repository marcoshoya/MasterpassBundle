<?php

/*
 * This file is part of the HoyaMasterpassBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hoya\MasterpassBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class BaseWebTestCase extends WebTestCase
{

    public static $container;

    protected function getContainer(array $options = array())
    {
        if (!static::$kernel) {
            static::$kernel = static::createKernel($options);
        }
        static::$kernel->boot();
        if (!static::$container) {
            static::$container = static::$kernel->getContainer();
        }
        static::$container->set('kernel', static::$kernel);

        return static::$container;
    }

    protected function deleteTmpDir($testCase)
    {
        if (!file_exists($dir = sys_get_temp_dir() . '/' . Kernel::VERSION . '/' . $testCase)) {
            return;
        }
        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass()
    {
        require_once __DIR__ . '/Functional/app/AppKernel.php';

        return 'Hoya\MasterpassBundle\Tests\Functional\app\AppKernel';
    }

    protected static function createKernel(array $options = array())
    {
        $class = self::getKernelClass();
        
        return new $class(
            'default', isset($options['debug']) ? $options['debug'] : true
        );
    }

}
