<?php

namespace Hoya\MasterpassBundle\Common;

use Hoya\MasterpassBundle\Helper\MasterpassHelper;
use Hoya\MasterpassBundle\Common\URL;

class Connector
{

    const AMP = "&";
    const QUESTION = "?";
    const EMPTY_STRING = "";
    const EQUALS = "=";
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
    const POST = "POST";
    const PUT = "PUT";
    const GET = "GET";
    const PKEY = 'pkey';
    const STRNATCMP = "strnatcmp";
    const SHA1 = "SHA1";
    const APPLICATION_XML = "application/xml; charset=utf-8;";
    const AUTHORIZATION = "Authorization";
    const OAUTH_BODY_HASH = "oauth_body_hash";
    const BODY = "body";
    const MESSAGE = 'Message';
    // Signature Base String
    const OAUTH_SIGNATURE = "oauth_signature";
    const OAUTH_CONSUMER_KEY = 'oauth_consumer_key';
    const OAUTH_NONCE = 'oauth_nonce';
    const SIGNATURE_METHOD = 'oauth_signature_method';
    const OAUTH_TIMESTAMP = 'oauth_timestamp';
    const OAUTH_CALLBACK = "oauth_callback";
    const OAUTH_SIGNATURE_METHOD = 'oauth_signature_method';
    const OAUTH_VERSION = 'oauth_version';
    // Srings to detect errors in the service calls
    const ERRORS_TAG = "<Errors>";
    const HTML_TAG = "<html>";
    const HTML_BODY_OPEN = '<body>';
    const HTML_BODY_CLOSE = '</body>';
    // Error Messages
    const EMPTY_REQUEST_TOKEN_ERROR_MESSAGE = 'Invalid Request Token';
    const INVALID_AUTH_URL = 'Invalid Auth Url';
    const POSTBACK_ERROR_MESSAGE = 'Postback Transaction Call was unsuccessful';
    //Connection Strings
    const CONTENT_TYPE_APPLICATION_XML = 'Content-Type: application/xml';
    const SSL_ERROR_MESSAGE = "SSL Error Code: %s %sSSL Error Message: %s";

    protected $urlService;
    public $signatureBaseString;
    public $authHeader;
    protected $consumerKey;
    protected $keystorePath;
    protected $keystorePassword;
    private $privateKey;
    private $version = '1.0';
    private $signatureMethod = 'RSA-SHA1';
    public $realm = "eWallet"; // This value is static

    /**
     * Constructor for Connector
     * @param string $consumerKey
     * @param string $privateKey
     */

    public function __construct(URL $url, $keys)
    {
        $this->urlService = $url;
        if ($this->urlService->isProduction()) {
            $this->consumerKey = $keys['production']['consumerkey'];
            $this->keystorePath = $keys['production']['keystorepath'];
            $this->keystorePassword = $keys['production']['keystorepassword'];
        } else {
            $this->consumerKey = $keys['sandbox']['consumerkey'];
            $this->keystorePath = $keys['sandbox']['keystorepath'];
            $this->keystorePassword = $keys['sandbox']['keystorepassword'];
        }

        $this->privateKey = $this->getPrivateKey();
    }

    public function getOriginUrl()
    {
        return $this->urlService->getOriginUrl();
    }

    public function getCallbackUrl()
    {
        return $this->urlService->getCallbackUrl();
    }

    /**
     * Method to retrieve the private key from the p12 file
     *
     * @return Private key string
     */
    private function getPrivateKey()
    {
        if (!$path = realpath($this->keystorePath)) {
            throw new \Exception("File {$this->keystorePath} does not exist");
        }

        if (!$pkcs12 = @file_get_contents($path)) {
            throw new \Exception("Cert file {$path} cannot be read");
        }

        $keystore = array();
        if (!openssl_pkcs12_read($pkcs12, $keystore, $this->keystorePassword)) {
            throw new \Exception("PKCS12 cannot be decoded");
        }

        return trim($keystore['pkey']);
    }

    public function doShoppingCart($params, $body)
    {
        return $this->doRequest($params, $this->urlService->getShoppingcartUrl(), Connector::POST, $body);
    }

    public function doAccessToken($params, $body)
    {
        return $this->doRequest($params, $this->urlService->getAccessUrl(), Connector::POST, $body);
    }

    public function doRequestToken($params, $body)
    {
        return $this->doRequest($params, $this->urlService->getRequestUrl(), Connector::POST, $body);
    }

    /**
     *  Method used for all Http connections
     *
     * @param $params
     * @param $url
     * @param $requestMethod
     * @param $body
     *
     * @throws Exception - When connection error
     *
     * @return mixed - Raw data returned from the HTTP connection
     */
    public function doRequest($params, $url, $requestMethod, $body = null)
    {

        if ($body != null) {
            $params[Connector::OAUTH_BODY_HASH] = $this->generateBodyHash($body);
        }

        try {
            return $this->connect($params, $this->realm, $url, $requestMethod, $body);
        } catch (\Exception $e) {
            throw $this->checkForErrors($e);
        }
    }

    protected function doRequest2($params, $url, $requestMethod, $body = null)
    {
        if ($body != null) {
            $params[Connector::OAUTH_BODY_HASH] = $this->generateBodyHash($body);
        }
    }

    /**
     * SDK:
     * Method to generate the body hash
     * 
     * @param $body
     * 
     * @return string
     */
    public function generateBodyHash($body)
    {
        $sha1Hash = sha1($body, true);

        return base64_encode($sha1Hash);
    }

    /**
     * This method generates and returns a unique nonce value to be used in
     * 	Wallet API OAuth calls.
     *
     * @param $length
     * 
     * @return string
     */
    private function generateNonce($length)
    {
        if (function_exists('com_create_guid') === true) {

            return trim(com_create_guid(), '{}');
        } else {
            $u = md5(uniqid('nonce_', true));

            return substr($u, 0, $length);
        }
    }

    /**
     * Builds a Auth Header used in connection to MasterPass services
     * 
     * @param $params
     * @param $realm
     * @param $url
     * @param $requestMethod
     * @param $body
     * 
     * @return string - Auth header
     */
    private function buildAuthHeaderString($params, $realm, $url, $requestMethod, $body)
    {
        $params = array_merge($this->oAuthParametersFactory(), $params);

        $signature = $this->generateAndSignSignature($params, $url, $requestMethod, $this->privateKey, $body);

        $params[Connector::OAUTH_SIGNATURE] = $signature;

        $startString = Connector::OAUTH_START_STRING;
        if (!empty($realm)) {
            $startString = $startString . Connector::REALM . Connector::EQUALS . Connector::DOUBLE_QUOTE . $realm . Connector::DOUBLE_QUOTE . Connector::COMMA;
        }

        foreach ($params as $key => $value) {
            $startString = $startString . $key . Connector::EQUALS . Connector::DOUBLE_QUOTE . MasterpassHelper::RFC3986urlencode($value) . Connector::DOUBLE_QUOTE . Connector::COMMA;
        }

        $this->authHeader = substr($startString, 0, strlen($startString) - 1);

        return $this->authHeader;
    }

    /**
     * Method to generate base string and generate the signature
     *  
     * @param $params
     * @param $url
     * @param $requestMethod
     * @param $privateKey
     * @param $body
     * 
     * @return string
     */
    private function generateAndSignSignature($params, $url, $requestMethod, $privateKey, $body)
    {
        $baseString = $this->generateBaseString($params, $url, $requestMethod);

        $this->signatureBaseString = $baseString;

        $signature = $this->sign($baseString, $privateKey);

        return $signature;
    }

    /**
     * Method to sign string
     * 
     * @param $string
     * @param $privateKey
     * 
     * @return string
     */
    private function sign($string, $privateKey)
    {
        $privatekeyid = openssl_get_privatekey($privateKey);

        $signature = null;
        openssl_sign($string, $signature, $privatekeyid, OPENSSL_ALGO_SHA1);

        return base64_encode($signature);
    }

    /**
     * Method to generate the signature base string 
     * 
     * @param $params
     * @param $url
     * @param $requestMethod
     * 
     * @return string
     */
    private function generateBaseString($params, $url, $requestMethod)
    {
        $urlMap = parse_url($url);

        $url = MasterpassHelper::formatUrl($url, $params);
        $params = MasterpassHelper::parseUrlParameters($urlMap, $params);

        $baseString = strtoupper($requestMethod) . Connector::AMP . MasterpassHelper::RFC3986urlencode($url) . Connector::AMP;
        ksort($params);

        $parameters = Connector::EMPTY_STRING;
        foreach ($params as $key => $value) {
            $parameters = $parameters . $key . Connector::EQUALS . MasterpassHelper::RFC3986urlencode($value) . Connector::AMP;
        }
        $parameters = MasterpassHelper::RFC3986urlencode(substr($parameters, 0, strlen($parameters) - 1));

        return $baseString . $parameters;
    }

    /**
     * Method to create all default parameters used in the base string and auth header
     * 
     * @return array
     */
    protected function oAuthParametersFactory()
    {
        if (null === $this->consumerKey) {
            throw new \Exception("Consumer key cannot be NULL");
        }

        $params = [
            Connector::OAUTH_CONSUMER_KEY => $this->consumerKey,
            Connector::OAUTH_SIGNATURE_METHOD => $this->signatureMethod,
            Connector::OAUTH_NONCE => $this->generateNonce(16),
            Connector::OAUTH_TIMESTAMP => time(),
            Connector::OAUTH_VERSION => $this->version
        ];

        return $params;
    }

    /**
     * General method to handle all HTTP connections
     * 
     * @param array $params
     * @param string $realm
     * @param string $url
     * @param string $requestMethod
     * @param string $body
     * 
     * @throws Exception - If connection fails or receives a HTTP status code > 300
     * 
     * @return mixed
     */
    private function connect($params, $realm, $url, $requestMethod, $body = null)
    {
        $curl = curl_init($url);

        // Adds the CA cert bundle to authenticate the SSL cert
        curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . Connector::SSL_CA_CER_PATH_LOCATION);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // This should always be TRUE to secure SSL connections

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            Connector::ACCEPT . Connector::COLON . Connector::SPACE . Connector::APPLICATION_XML,
            Connector::CONTENT_TYPE . Connector::COLON . Connector::SPACE . Connector::APPLICATION_XML,
            Connector::AUTHORIZATION . Connector::COLON . Connector::SPACE . $this->buildAuthHeaderString($params, $realm, $url, $requestMethod, $body)
        ));

        if ($requestMethod == Connector::GET) {
            curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($requestMethod));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($curl);
        //print_r($result);
        // Check if any error occurred
        if (curl_errno($curl)) {
            throw new \Exception(sprintf(Connector::SSL_ERROR_MESSAGE, curl_errno($curl), PHP_EOL, curl_error($curl)), curl_errno($curl));
        }


        // Check for errors and throw an exception
        if (($errorCode = curl_getinfo($curl, CURLINFO_HTTP_CODE)) > 300) {
            throw new \Exception($result, $errorCode);
        }

        return $result;
    }

    /**
     * Method to check for HTML content in the exception message and remove everything except the body
     * 
     * @param Exception $e
     * 
     * @return Exception
     */
    private function checkForErrors(\Exception $e)
    {
        if (strpos($e->getMessage(), Connector::HTML_TAG) !== false) {
            $body = substr($e->getMessage(), strpos($e->getMessage(), Connector::HTML_BODY_OPEN) + 6, strpos($e->getMessage(), Connector::HTML_BODY_CLOSE));

            return new \Exception($body);
        } else {

            return $e;
        }
    }

}
