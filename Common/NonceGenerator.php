<?php

namespace Hoya\MasterpassBundle\Common;

class NonceGenerator
{
    const BITS_OF_ENTROPY = 64;

    /**
     * This method generates and returns a unique nonce value to be used in
     * Wallet API OAuth calls.
     *
     * Addendum: The old code for nonce generation was basically this:
     *
     *      return substr(md5(uniqid('nonce_', true)), 0, 16);
     *
     * Since it produced 16 char long strings of "random" hex data, this
     * new implementation returns strings that look exactly the same but are
     * actually generated with PHP7 random_bytes (or the paragonie polyfill for that function)
     *
     * @return string A random 8 byte sequence encoded as a hex string (thus 16 chars long)
     */
    public static function generate()
    {
        return bin2hex(random_bytes(self::BITS_OF_ENTROPY / 8));
    }
}
