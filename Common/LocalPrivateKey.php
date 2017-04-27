<?php

namespace Hoya\MasterpassBundle\Common;

/**
 * The LocalPrivateKey represents a private key stored
 * in the PKCS#12 format in a file of the local filesystem.
 */
class LocalPrivateKey implements PrivateKeyInterface
{
    /**
     * @var EncryptedPrivateKey
     */
    private $privateKey;

    /**
     * @param string $keystorePath Path to the PKCS#12 keystore
     * @param string $passphrase   Passphrase for unlocking the keystore
     *
     * @throws \Exception When the keystore file does not exist
     *                    or cannot be read
     */
    public function __construct($keystorePath, $passphrase)
    {
        $this->privateKey = new EncryptedPrivateKey(
            $this->retrieveFileContents($keystorePath), $passphrase
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return $this->privateKey->getContents();
    }

    /**
     * @param string $path A file path in the local filesystem
     *
     * @return string The file contents
     *
     * @throws \Exception When the keystore file does not exist
     *                    or cannot be read
     */
    private function retrieveFileContents($path)
    {
        if (!$realPath = realpath($path)) {
            throw new \Exception("File $path does not exist");
        }

        if (false === $content = @file_get_contents($realPath)) {
            throw new \Exception("File $realPath cannot be read");
        }

        return $content;
    }
}
