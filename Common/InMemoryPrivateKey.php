<?php

namespace Hoya\MasterpassBundle\Common;

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
