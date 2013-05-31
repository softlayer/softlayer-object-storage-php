<?php
/**
* ObjectStorage_Http_Client that uses CURL
*
* @package ObjectStorage-Client
* @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
*/
class ObjectStorage_Http_Adapter_Curl implements ObjectStorage_Http_Adapter_Interface
{
    protected $uri;
    protected $headers;
    protected $body;
    protected $method;
    protected $timeout = 30;
    protected $requestHeaders = array();
    protected $fileHandler = null;

    public function __construct($options = array())
    {
        if  (extension_loaded('curl') && function_exists('curl_version')) {
            $curlVersion = curl_version();

            if (! isset($curlVersion['protocols']) || ! in_array('https', $curlVersion['protocols'])) {
                throw new ObjectStorage_Exception('CURL does not supported HTTPS.');
            }
        } else {
            throw new ObjectStorage_Exception('CURL is not supported on this server.');
        }

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
        $this->uri = $uri;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setHeaders()
     */
    public function setHeaders($name, $value)
    {
        $this->headers[$name] = $name . ': ' . $value;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setBody()
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setFileHandler()
     */
    public function setFileHandler($handler)
    {
        $this->fileHandler = $handler;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::setMethod()
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::reset()
     */
    public function reset()
    {
        $this->headers = array();
        $this->body    = '';
        $this->method  = '';
        $this->fileHandler = null;

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::request()
     */
    public function request()
    {
        $curl = curl_init();

        $urlInfo = parse_url($this->uri);

        $requiredHeaders[] = 'Host: ' . $urlInfo['host'];
        $requiredHeaders[] = 'Connection: close';
        $requiredHeaders[] = 'Expect:';

        $this->requestHeaders = array_merge($requiredHeaders, $this->headers);

        // To get around CURL issue. http://curl.haxx.se/mail/lib-2010-08/0171.html
        $requestHeaders = implode("\r\n", $this->requestHeaders);

        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->uri);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($requestHeaders));

        $method = strtoupper($this->method);
        switch($method) {
            case 'HEAD':
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        if (in_array($method, array('PUT', 'POST'))) {

            if ($this->fileHandler != null) {

                curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($curl, CURLOPT_INFILE, $this->fileHandler);
                curl_setopt($curl, CURLOPT_READFUNCTION, array(&$this, 'readFileCallback'));

            } else if ($this->body != '') {
                $filePointer = fopen('php://temp/maxmemory:256000', 'w');
                if (! $filePointer) {
                    throw new ObjectStorage_Exception('could not open temp memory data');
                }
                fwrite($filePointer, $this->body);
                fseek($filePointer, 0);

                curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($curl, CURLOPT_INFILE, $filePointer);
                curl_setopt($curl, CURLOPT_INFILESIZE, strlen($this->body));
            }
        }

        $rawResponse = curl_exec($curl);

        if ($rawResponse === false) {
            throw new ObjectStorage_Exception(curl_error($curl));
        }

        $curlInfo = curl_getinfo($curl);
        $rawHeaders = substr($rawResponse, 0, $curlInfo['header_size']);

        $headers = array();
        if ($rawHeaders != '') {
            $headerLines = explode("\n", $rawHeaders);
            foreach ($headerLines as $line) {
                $headerChunk = explode(': ', $line);

                if (count($headerChunk) == 2) {
                    $headers[ucfirst(strtolower($headerChunk[0]))] = trim($headerChunk[1]);
                }
            }
        }
        $body = substr($rawResponse, $curlInfo['header_size']);
        $statusCode = $curlInfo['http_code'];

        curl_close($curl);

        $response = new ObjectStorage_Http_Response();
        $response->setStatusCode($statusCode);
        $response->setHeaders($headers);
        $response->setBody($body);

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

    protected function readFileCallback($curl, $fileHandler, $length = 1000)
    {
        $data = fread($fileHandler, $length);
        $len = strlen($data);

        return $data;
    }
}