<?php
/**
* ObjectStorage token store interface
*
* @see ObjectStorage_TokenStore
*
* @package ObjectStorage-Client
* @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
*/
interface ObjectStorage_TokenStore_Interface
{
    /**
     * Returns a cached value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Sets a value
     *
     * @param string $key
     * @param mixed $data
     *
     * @return bool
     */
    public function set($key, $data);

    /**
     * Deletes the cache value
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key);
}