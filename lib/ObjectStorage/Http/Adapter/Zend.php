<?php
/**
* ObjectStorage_Http_Client that uses Zend_HTTP_Client.
* If your server doesn't support CURL and you're already using Zend, this adapter could be used.
*
* @package ObjectStorage-Client
* @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
*/
class ObjectStorage_Http_Adapter_Zend implements ObjectStorage_Http_Adapter_Interface
{
    protected $client;
    protected $headers;
    protected $timeout = 30;
    protected $requestHeaders = array();

    public function __construct($options = array())
    {
        $this->client = new Zend_Http_Client();

        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setUri()
     */
    public function setUri($uri)
    {
        $this->client->setUri($uri);
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setHeaders()
     */
    public function setHeaders($name, $value)
    {
        $this->headers[$name] = $name . ': ' . $value;
        $this->client->setHeaders($name, $value);
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setBody()
     */
    public function setBody($body)
    {
        $this->client->setRawData($body);
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setFileHandler()
     */
    public function setFileHandler($handler)
    {
        $requiredVersion = '1.10';

        if (Zend_Version::compareVersion($requiredVersion) >= 1) {
            throw new ObjectStorage_Exception('Zend HTTP clien\'s data streaming upload requires Zend framework version ' . $requiredVersion . ' or greater.');
        }
        $this->client->setRawData($handler);
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setMethod()
     */
    public function setMethod($method)
    {
        $this->client->setMethod($method);
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::reset()
     */
    public function reset()
    {
        if (count($this->headers) > 0) {
            foreach ($this->headers as $key => $val) {
                $this->client->setHeaders($key, null);
            }
        }
        $this->headers = array();
        $this->client->resetParameters();
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::request()
     */
    public function request()
    {
        $this->requestHeaders = $this->headers;

        $this->client->setConfig(array('timeout', $this->timeout));
        $result =  $this->client->request();

        $response = new ObjectStorage_Http_Response();
        $response->setStatusCode($result->getStatus());
        $response->setHeaders($result->getHeaders());
        $response->setBody($result->getBody());

        return $response;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::getLastRequestHeaders()
     */
    public function getLastRequestHeaders()
    {
        return $this->requestHeaders;
    }
}