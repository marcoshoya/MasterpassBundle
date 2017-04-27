<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * The PrivateKeyInterface abstraction allows the implementation
 * of different strategies for storing and retrieving RSA private
 * keys while at the same time providing a unified interface to
 * client code for accessing them.
 */
interface PrivateKeyInterface
{
    /**
     * This method must return an RSA private key in the PEM format.
     *
     * @return string
     */
    public function getContents();
}
