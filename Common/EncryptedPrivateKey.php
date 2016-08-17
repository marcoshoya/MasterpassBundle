<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * The EncryptedPrivateKey holds an in-memory PKCS#12 string
 * and the passphrase needed to decrypt it.
 */
class EncryptedPrivateKey implements PrivateKeyInterface
{
    /**
     * @var InMemoryPrivateKey
     */
    private $cachedKey;

    /**
     * @var string
     */
    private $pkcs12string;

    /**
     * @var string
     */
    private $passphrase;

    /**
     * @param string $pkcs12string Keystore raw content
     * @param string $passphrase   Keystore passphrase
     */
    public function __construct($pkcs12string, $passphrase)
    {
        $this->cachedKey = null;
        $this->pkcs12string = $pkcs12string;
        $this->passphrase = $passphrase;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception When the pkcs12 string cannot be decrypted
     */
    public function getContents()
    {
        if (null === $this->cachedKey) {
            $this->cachedKey = new InMemoryPrivateKey($this->decryptPKCS12string());
        }

        return $this->cachedKey->getContents();
    }

    /**
     * @return string A plaintext private key in PEM format
     *
     * @throws \Exception When the pkcs12 string cannot be decrypted
     */
    private function decryptPKCS12string()
    {
        $keystore = [];
        if (!@openssl_pkcs12_read($this->pkcs12string, $keystore, $this->passphrase)) {
            throw new \Exception('PKCS#12 cannot be decrypted');
        }

        return $keystore['pkey'];
    }
}
