<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * The InMemoryPrivateKey is a simple wrapper around
 * a plaintext private key.
 *
 * This implementation of PrivateKeyInterface can be used
 * for testing purposes and/or building in-memory caching
 * in other, more complex implementations (@see EncryptedPrivateKey).
 */
class InMemoryPrivateKey implements PrivateKeyInterface
{
    /**
     * @var string
     */
    private $privateKey;

    /**
     * @param string $privateKey
     */
    public function __construct($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return $this->privateKey;
    }
}
