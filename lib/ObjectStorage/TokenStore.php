<?php
/**
 * ObjectStorage token store
 * A token store is a place that ObjectStorage can store ObjectStorage authentication token for reusing it.
 *
 * @see ObjectStorage::setTokenStore
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_TokenStore
{
    private function __construct()
    {
    }

    /**
     * Returns a Token Store object
     *
     * <code>
     * $tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp/objectStorage'));
     * ObjectStorage::setTokenStore($tokenStore);
     *
     * $objectStorageDallas = new ObjectStorage($objectStorageHost01, $username01, $password01);
     * </code>
     *
     * @param string $type
     * @param array $config
     *
     * @throws ObjectStorage_Exception_TokenStore
     *
     * @return ObjectStorage_TokenStore_Interface
     */
    public static function factory($type, $config = array())
    {
        switch(strtoupper($type)) {
            case 'FILE':
                return new ObjectStorage_TokenStore_File($config);
            case 'MEMORY':
                return new ObjectStorage_TokenStore_Memory($config);
            default:
                throw new ObjectStorage_Exception_TokenStore("Token store type '{$type}' is not implemented.");
        }
    }
}
