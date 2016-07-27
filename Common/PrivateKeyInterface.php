<?php

namespace Hoya\MasterpassBundle\Common;

interface PrivateKeyInterface
{
    /**
     * This method must return the raw private
     * key from a PKCS#12 certificate.
     *
     * @return string
     */
    public function getContents();
}
