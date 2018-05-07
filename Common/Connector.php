<?php

namespace Hoya\MasterpassBundle\Common;

use Symfony\Bridge\Monolog\Logger;
use Hoya\MasterpassBundle\Helper\MasterpassHelper;

/**
 * Connector class
 *
 * @author Marcos Lazarin <marcoshoya at gmail dot com>
 */
class Connector {

    const AMP = '&';
    const QUESTION = '?';
    const EMPTY_STRING = '';
    const EQUALS = '=';
    const DOUBLE_QUOTE = '"';
    const COMMA = ',';
    const COLON = ':';
    const SPACE = ' ';
    const UTF_8 = 'UTF-8';
    const V1 = 'v1';
    const OAUTH_START_STRING = 'OAuth ';
    const REALM = 'realm';
    const ACCEPT = 'Accept';
    const CONTENT_TYPE = 'Content-Type';
    const SSL_CA_CER_PATH_LOCATION = '/SSLCerts/EnTrust/cacert.pem';
    const POST = 'POST';
    const PUT = 'PUT';
    const GET = 'GET';
    const PKEY = 'pkey';
    const STRNATCMP = 'strnatcmp';
    const SHA1 = 'SHA1';
    const APPLICATION_XML = 'application/xml; charset=utf-8;';
    const AUTHORIZATION = 'Authorization';
    const OAUTH_BODY_HASH = 'oauth_body_hash';
    const BODY = 'body';
    const MESSAGE = 'Message';
    // Signature Base String
    const OAUTH_SIGNATURE = 'oauth_signature';
    const OAUTH_CONSUMER_KEY = 'oauth_consumer_key';
    const OAUTH_NONCE = 'oauth_nonce';
    const SIGNATURE_METHOD = 'oauth_signature_method';
    const OAUTH_TIMESTAMP = 'oauth_timestamp';
    const OAUTH_CALLBACK = 'oauth_callback';
    const OAUTH_SIGNATURE_METHOD = 'oauth_signature_method';
    const OAUTH_VERSION = 'oauth_version';
    // Strings to detect errors in the service calls
    const ERRORS_TAG = '<Errors>';
    const HTML_TAG = '<html>';
    const HTML_BODY_OPEN = '<body>';
    const HTML_BODY_CLOSE = '</body>';
    //Connection Strings
    const CONTENT_TYPE_APPLICATION_XML = 'Content-Type: application/xml';
    const SSL_ERROR_MESSAGE = 'SSL Error Code: %s %sSSL Error Message: %s';

    protected $urlService;
    public $signatureBaseString;
    public $authHeader;
    protected $consumerKey;
    private $privateKey;
    private $version = '1.0';
    private $signatureMethod = 'RSA-SHA1';
    public $realm = 'eWallet'; // This value is static
    public $errorMessage = null;

    /**
     * @var Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @param URL   $url
     * @param array $keys
     */
    public function __construct(Logger $logger, URL $url, array $keys)
    {
        $this->logger = $logger;
        $this->urlService = $url;
        $this->consumerKey = $keys['consumerkey'];
        $this->privateKey = new LocalPrivateKey($keys['keystorepath'], $keys['keystorepassword']);
    }

    /**
     * This method allows the class client to override the
     * private key passed in the constructor.
     *
     * @param PrivateKeyInterface $privateKey
     *
     * @return Connector
     */
    public function setPrivateKey(PrivateKeyInterface $privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Returns the consumer key according environment.
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->urlService->getCallbackUrl();
    }

    /**
     * Get logger
     * 
     * @return Symfony\Bridge\Monolog\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param array       $params
     * @param string|null $body
     *
     * @return string
     */
    public function doPaymentData($tid, $cartid, $checkoutid)
    {
        $params = [];
        $url = $this->urlService->getPaymentdataUrl($tid, $cartid, $checkoutid);
                
        return $this->doRequest($params, $url, self::GET);
    }

    /**
     *  Method used for all Http connections.
     *
     * @param array       $params
     * @param string      $url
     * @param string      $requestMethod
     * @param string|null $body
     *
     * @throws \Exception - When connection error
     *
     * @return mixed - Raw data returned from the HTTP connection
     */
    public function doRequest($params, $url, $requestMethod, $body = null)
    {
        if ($body !== null) {
            $params[self::OAUTH_BODY_HASH] = $this->generateBodyHash($body);
        }

        try {
            return $this->connect($params, $this->realm, $url, $requestMethod, $body);
        } catch (\Exception $e) {
            $this->errorMessage = $this->checkForErrors($e);
        }
    }

    /**
     * SDK:
     * Method to generate the body hash.
     *
     * @param string $body
     *
     * @return string
     */
    public function generateBodyHash($body)
    {
        $sha1Hash = sha1($body, true);

        return base64_encode($sha1Hash);
    }

    /**
     * Builds a Auth Header used in connection to Masterpass services.
     *
     * @param array  $params
     * @param string $realm
     * @param string $url
     * @param string $requestMethod
     *
     * @return string - Auth header
     */
    private function buildAuthHeaderString($params, $realm, $url, $requestMethod)
    {
        $params = array_merge($this->oAuthParametersFactory(), $params);

        $signature = $this->generateAndSignSignature($params, $url, $requestMethod);

        $params[self::OAUTH_SIGNATURE] = $signature;

        $startString = self::OAUTH_START_STRING;
        if (!empty($realm)) {
            $startString = $startString . self::REALM . self::EQUALS . self::DOUBLE_QUOTE . $realm . self::DOUBLE_QUOTE . self::COMMA;
        }

        foreach ($params as $key => $value) {
            $startString = $startString . $key . self::EQUALS . self::DOUBLE_QUOTE . MasterpassHelper::RFC3986urlencode($value) . self::DOUBLE_QUOTE . self::COMMA;
        }

        $this->authHeader = substr($startString, 0, strlen($startString) - 1);

        return $this->authHeader;
    }

    /**
     * Method to generate base string and generate the signature.
     *
     * @param array  $params
     * @param string $url
     * @param string $requestMethod
     *
     * @return string
     */
    private function generateAndSignSignature($params, $url, $requestMethod)
    {
        $baseString = $this->generateBaseString($params, $url, $requestMethod);

        $this->signatureBaseString = $baseString;

        $signature = $this->sign($baseString);

        return $signature;
    }

    /**
     * Method to sign string.
     *
     * @param string $string
     *
     * @return string
     */
    private function sign($string)
    {
        $signature = null;
        openssl_sign($string, $signature, $this->privateKey->getContents(), OPENSSL_ALGO_SHA1);

        return base64_encode($signature);
    }

    /**
     * Method to generate the signature base string.
     *
     * @param array  $params
     * @param string $url
     * @param string $requestMethod
     *
     * @return string
     */
    private function generateBaseString($params, $url, $requestMethod)
    {
        $urlMap = parse_url($url);

        $url = MasterpassHelper::formatUrl($url, $params);
        $params = MasterpassHelper::parseUrlParameters($urlMap, $params);

        $baseString = strtoupper($requestMethod) . self::AMP . MasterpassHelper::RFC3986urlencode($url) . self::AMP;
        ksort($params);

        $parameters = self::EMPTY_STRING;
        foreach ($params as $key => $value) {
            $parameters = $parameters . $key . self::EQUALS . MasterpassHelper::RFC3986urlencode($value) . self::AMP;
        }
        $parameters = MasterpassHelper::RFC3986urlencode(substr($parameters, 0, strlen($parameters) - 1));

        return $baseString . $parameters;
    }

    /**
     * Method to create all default parameters used in the base string and auth header.
     *
     * @return array
     *
     * @throws \Exception When the consumer key has not been provided to the service
     */
    protected function oAuthParametersFactory()
    {
        if (null === $this->consumerKey) {
            throw new \Exception('Consumer key cannot be NULL');
        }

        $params = [
            self::OAUTH_CONSUMER_KEY => $this->consumerKey,
            self::OAUTH_SIGNATURE_METHOD => $this->signatureMethod,
            self::OAUTH_NONCE => NonceGenerator::generate(),
            self::OAUTH_TIMESTAMP => time(),
            self::OAUTH_VERSION => $this->version,
        ];

        return $params;
    }

    /**
     * General method to handle all HTTP connections.
     *
     * @param array       $params
     * @param string      $realm
     * @param string      $url
     * @param string      $requestMethod
     * @param string|null $body
     *
     * @throws \Exception - If connection fails or receives a HTTP status code > 300
     *
     * @return mixed
     */
    private function connect($params, $realm, $url, $requestMethod, $body = null)
    {
        $curl = curl_init($url);

        // Adds the CA cert bundle to authenticate the SSL cert
        curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . self::SSL_CA_CER_PATH_LOCATION);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // This should always be TRUE to secure SSL connections

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            self::ACCEPT . self::COLON . self::SPACE . self::APPLICATION_XML,
            self::CONTENT_TYPE . self::COLON . self::SPACE . self::APPLICATION_XML,
            self::AUTHORIZATION . self::COLON . self::SPACE . $this->buildAuthHeaderString($params, $realm, $url, $requestMethod),
        ));

        if ($requestMethod == self::GET) {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($requestMethod));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $this->getLogger()->debug("[Hoya\MasterpassBundle\Common\Connector] calling {$url}");
        $this->getLogger()->debug("[Hoya\MasterpassBundle\Common\Connector] body content: {$body}");

        $result = curl_exec($curl);

        // Check if any error occurred
        if (curl_errno($curl)) {
            throw new \Exception(sprintf(self::SSL_ERROR_MESSAGE, curl_errno($curl), PHP_EOL, curl_error($curl)), curl_errno($curl));
        }

        // Check for errors and throw an exception
        if (($errorCode = curl_getinfo($curl, CURLINFO_HTTP_CODE)) > 300) {
            throw new \Exception($result, $errorCode);
        }

        return $result;
    }

    /**
     * Method to check for HTML content in the exception message and remove everything except the body.
     *
     * @param \Exception $e
     *
     * @return \Exception
     */
    private function checkForErrors(\Exception $e)
    {
        if (strpos($e->getMessage(), self::HTML_TAG) !== false) {
            $body = substr($e->getMessage(), strpos($e->getMessage(), self::HTML_BODY_OPEN) + 6, strpos($e->getMessage(), self::HTML_BODY_CLOSE));

            return $body;
        } else {
            return MasterpassHelper::formatXML($e->getMessage());
        }
    }

}
