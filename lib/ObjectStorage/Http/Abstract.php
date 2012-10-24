<?php
/**
 * ObjectStorage_Http_Abstract is the base class of a HTTP request and response classes.
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Http_Abstract
{
    /**
     * Array of HTTP headers
     *
     * @var array
     */
    protected $headers = array();

    /**
     * HTTP body
     *
     * @var mixed
     */
    protected $body = '';

    /**
     * Returns header by key
     *
     * @param string $headerKey
     *
     * @return string
     */
    public function getHeader($headerKey)
    {
        $key = $this->cleanHeaderKey($headerKey);
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return null;
    }

    /**
     * Returns header array
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets header
     *
     * @param string $headerKey
     * @param string $value
     */
    public function setHeader($headerKey = '', $value = '')
    {
        $this->headers[$this->cleanHeaderKey($headerKey)] = $value;
    }

    /**
     * Deletes a header by key
     *
     * @param string $headerKey
     */
    public function deleteHeader($headerKey = '')
    {
        if (isset($this->headers[$this->cleanHeaderKey($headerKey)])) {
            unset($this->headers[$this->cleanHeaderKey($headerKey)]);
        }
    }

    /**
     * Sets header array
     *
     * @param array $headerArray
     */
    public function setHeaders($headerArray = array())
    {
        if (! is_array($headerArray)) {
            throw new ObjectStorage_Exception('HTTP headers must be an array.');
        }
        foreach ($headerArray as $headerKey => $value) {
            $this->setHeader($headerKey, $value);
        }
    }

    /**
     * Returns HTTP body
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets HTTP body
     *
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    protected function cleanHeaderKey($headerKey)
    {
        return ucfirst(strtolower($headerKey));
    }
}