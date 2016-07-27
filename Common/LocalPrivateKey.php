<?php

namespace Hoya\MasterpassBundle\Common;

class LocalPrivateKey implements PrivateKeyInterface
{
    /**
     * @var string
     */
    private $cachedPrivateKey;

    /**
     * @var string
     */
    private $keystorePath;

    /**
     * @var string
     */
    private $keystorePassword;

    /**
     * @param string $keystorePath
     * @param string $keystorePassword
     */
    public function __construct($keystorePath, $keystorePassword)
    {
        $this->cachedPrivateKey = null;
        $this->keystorePath = $keystorePath;
        $this->keystorePassword = $keystorePassword;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception When the keystore file does not exist,
     *                    cannot be read or cannot be decoded.
     */
    public function getPrivateKey()
    {
        if (null === $this->cachedPrivateKey) {
            $this->cachedPrivateKey = $this->retrievePKFromDisk();
        }

        return $this->cachedPrivateKey;
    }

    /**
     * @return string
     *
     * @throws \Exception When the keystore file does not exist,
     *                    cannot be read or cannot be decoded.
     */
    private function retrievePKFromDisk()
    {
        if (!$path = realpath($this->keystorePath)) {
            throw new \Exception("File {$this->keystorePath} does not exist");
        }

        if (!$pkcs12 = @file_get_contents($path)) {
            throw new \Exception("Cert file {$path} cannot be read");
        }

        $keystore = [];
        if (!@openssl_pkcs12_read($pkcs12, $keystore, $this->keystorePassword)) {
            throw new \Exception('PKCS12 cannot be decoded');
        }

        return $keystore['pkey'];
    }
}
