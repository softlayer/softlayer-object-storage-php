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
     * @param string $hederKey
     *
     * @return string
     */
    public function getHeader($hederKey)
    {
        $key = $this->cleanHeaderKey($hederKey);
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
     * @param string $hederKey
     * @param string $value
     */
    public function setHeader($hederKey = '', $value = '')
    {
        $this->headers[$this->cleanHeaderKey($hederKey)] = (string) $value;
    }

    /**
     * Deletes a header by key
     *
     * @param string $hederKey
     */
    public function deleteHeader($hederKey = '')
    {
        if (isset($this->headers[$this->cleanHeaderKey($hederKey)])) {
            unset($this->headers[$this->cleanHeaderKey($hederKey)]);
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
        foreach ($headerArray as $hederKey => $value) {
            $this->setHeader($hederKey, $value);
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

    protected function cleanHeaderKey($hederKey)
    {
        return ucfirst(strtolower($hederKey));
    }
}