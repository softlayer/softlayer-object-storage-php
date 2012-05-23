<?php
/**
* ObjectStorage_Http_Client socket adapter
*
* @package ObjectStorage-Client
* @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
*/
class ObjectStorage_Http_Adapter_Socket implements ObjectStorage_Http_Adapter_Interface
{
    protected $uri;
    protected $headers;
    protected $body;
    protected $method;
    protected $timeout = 30;
    protected $requestHeaders = array();
    protected $fileHander = null;

    public function __construct($options = array())
    {
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
        $this->fileHander = $handler;
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

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Http_Adapter_Interface::request()
     */
    public function request()
    {
        $urlInfo = parse_url($this->uri);

        if (! isset($urlInfo['port'])) {
            $urlInfo['port'] = $urlInfo['scheme'] == 'https' ? 443 : 80;
        }

        $scheme = $urlInfo['scheme'] == 'https' ? 'ssl://' : '';

        if (($socket = @fsockopen($scheme . $urlInfo['host'], $urlInfo['port'], $errno, $errstr, $this->timeout)) == false) {
            throw new ObjectStorage_Exception($errstr);
        }

        stream_set_timeout($socket, 0, $this->timeout * 1000);

        $statusCode = 0;
        $this->requestHeaders = array();
        $lineBreak = "\r\n";
        $doubleLineBreaks = $lineBreak . $lineBreak;
        $requestData = $responseData = '';


        $this->requestHeaders[] = $this->method . ' ' . $urlInfo['path'] . (isset($urlInfo['query']) ? '?' . $urlInfo['query'] : '') . ' HTTP/1.1';
        $this->requestHeaders[] = 'Host: ' . $urlInfo['host'];
        $this->requestHeaders[] = 'Connection: Close';

        if (count($this->headers) > 0) {
            $this->requestHeaders = array_merge($this->requestHeaders, $this->headers);
        }

        $requestData = implode($lineBreak, $this->requestHeaders) . $doubleLineBreaks;

        fputs($socket, $requestData);

        if (in_array($this->method, array('PUT', 'POST'))) {
            if (is_resource($this->fileHander)) {

                $fseek = 0; // Used for debugging purposes
                $readSize = 1024;

                while (! feof($this->fileHander)) {

                    $contents = fread($this->fileHander, $readSize);

                    if (@fwrite($socket, $contents) === false) {
                        throw new ObjectStorage_Exception('Failed to write data to socket when writing about ' . $fseek . ' bytes.');
                    }

                    $fseek += $readSize;
                }

                fclose($this->fileHander);

            } else if ($this->body != '') {
                fputs($socket, $this->body);
            }
        }

        while(!feof($socket)) {
            $responseData .= fgets($socket, 128);
        }
        fclose($socket);

        $headerEndingPosition = strpos($responseData, $doubleLineBreaks);
        $rawHeaders = substr($responseData, 0, $headerEndingPosition);

        $headers = array();
        if ($rawHeaders != '') {
            $headerLines = explode("\n", $rawHeaders);
            $isFirst = true;
            foreach ($headerLines as $line) {

                if ($isFirst == true) {
                    $statusChunks = explode(' ', $line);
                    $statusCode = (int) $statusChunks[1];
                    $isFirst = false;
                }

                $headerChunk = explode(': ', $line);

                if (count($headerChunk) == 2) {
                    $headers[ucfirst(strtolower($headerChunk[0]))] = trim($headerChunk[1]);
                }
            }
        }
        $body = substr($responseData, $headerEndingPosition + strlen($doubleLineBreaks));

        $response = new ObjectStorage_Http_Response();
        $response->setStatusCode($statusCode);
        $response->setHeaders($headers);
        $response->setBody($body);

        return $response;
    }

    public function getLastRequestHeaders()
    {
        return $this->requestHeaders;
    }
}