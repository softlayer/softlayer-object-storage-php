<?php
/**
 * This token store utilized a temporary file to store ObjectStorage authentication data
 *
 * @see ObjectStorage::setTokenStore
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_TokenStore_File implements ObjectStorage_TokenStore_Interface
{
    private $filePath;

    const TIMESTAMP_DELIMITER = '||';

    public function __construct($config)
    {
        if (isset($config['path'])) {
            $this->filePath = $config['path'] . '/objectStorage_key_';
        } else {
            $this->filePath = '/tmp/objectStorage_key_';
        }

        $this->ttl = isset($config['ttl']) && (int) $config['ttl'] > 60 ? (int) $config['ttl'] : 3600;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::get()
     */
    public function get($key)
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {

            if (! is_readable($filePath)) {
                throw new ObjectStorage_Exception_TokenStore('Failed to retrieve data from file store.');
            }

            $data = file_get_contents($filePath);

            $delimiterPosition = strpos($data, self::TIMESTAMP_DELIMITER);
            $delimiterLength   = strlen(self::TIMESTAMP_DELIMITER);

            $expirationTime = substr($data, 0, $delimiterPosition);

            if ($expirationTime > time()) {
                return unserialize(substr($data, $delimiterPosition + $delimiterLength));
            } else {
                $this->delete($key);
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::set()
     */
    public function set($key, $data)
    {
        $filePath = $this->getFilePath($key);
        $expirationTime = time() + $this->ttl;

        $result = file_put_contents($filePath,  $expirationTime . self::TIMESTAMP_DELIMITER . serialize($data));
        if ($result === false || ! is_readable($filePath)) {
            throw new ObjectStorage_Exception_TokenStore('Failed to set the data to file store.');
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_TokenStore_Interface::delete()
     */
    public function delete($key)
    {
        $filePath = $this->getFilePath($key);
        return unlink($filePath);
    }

    private function getFilePath($key)
    {
        $encryptedKey = str_replace('=', '', sha1($key));
        return $this->filePath . substr($encryptedKey, 0, 64);
    }
}