<?php
/**
 * A temporary token store that uses memory as token storage.
 * This is NOT a persistent data store nor it's anywhere close to memcache.
 * A token stored in this object will be removed at the end of script execution.
 *
 * @see ObjectStorage::setTokenStore
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_TokenStore_Memory implements ObjectStorage_TokenStore_Interface
{
    protected $data = array();

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::get()
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return unserialize($this->data[$key]);
        }

        return null;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::set()
     */
    public function set($key, $data)
    {
        $this->data[$key] = serialize($data);
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::delete()
     */
    public function delete($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
        return true;
    }
}