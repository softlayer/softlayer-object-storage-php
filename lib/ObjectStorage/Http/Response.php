<?php
/**
 * ObjectStorage_Http_Response contains a HTTP response data.
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Http_Response extends ObjectStorage_Http_Abstract
{
    /**
     * HTTP response code
     *
     * @var int
     */
    protected $statusCode = 0;

    /**
     * Returns HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets HTTP status code
     *
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        if (! is_int($statusCode)) {
            throw new ObjectStorage_Exception('HTTP status code must be an integer.');
        }
        $this->statusCode = $statusCode;
    }
}