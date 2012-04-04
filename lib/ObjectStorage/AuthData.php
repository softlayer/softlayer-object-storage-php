<?php
/**
 * This class holds a ObjectStorage URL and authentication token
 *
 * @see ObjectStorage::getAuthenticationData
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_AuthData
{
    /**
     * ObjectStorage base URL
     *
     * @var string
     */
    public $objectStorageUrl;

    /**
     * ObjectStorage authentication token
     *
     * @var string
     */
    public $authToken;
}