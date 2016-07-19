<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * This enumeration defines the status values available for a submitted transaction.
 */
class Environment
{
    /**
     * Denotes that a transaction failed.
     */
    const SANDBOX = 'sandbox';

    /**
     * Denotes that a transaction succeeded.
     */
    const PRODUCTION = 'production';
}
