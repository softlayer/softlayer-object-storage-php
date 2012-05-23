<?php
/**
 * Interface for ObjectStorage REST client adpater class
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
Interface ObjectStorage_Http_Adapter_Interface
{
    /*
     * Sets URI. URI is typically a ObjectStorage URI
     *
     * @param string $uri
     *
     * @return void
     */
    public function setUri($uri);

    /**
     * Sets request header
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function setHeaders($name, $value);

    /**
     * Sets request body
     *
     * @param mixed $body
     *
     * @return void
     */
    public function setBody($body);

    /**
     * Sets request method such as GET, PUT, POST, DELETE or HEAD
     *
     * @param string $method
     *
     * @return void
     */
    public function setMethod($method);

    /**
     * Initialize request headers, body and method.
     *
     * @return bool
     */
    public function reset();

    /**
     * Performs a HTTP request and returns ObjectStorage_Http_Response object
     *
     * @return ObjectStorage_Http_Response
     */
    public function request();

    /**
     * Returns the last request headers
     *
     * @return array
     */
    public function getLastRequestHeaders();

    /**
     * Returns the last request headers
     *
     * @param resource $handler
     *
     * @return void
     */
    public function setFileHandler($handler);
}