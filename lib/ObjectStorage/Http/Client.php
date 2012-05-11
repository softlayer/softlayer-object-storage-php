<?php
/**
 * ObjectStorage_Http_Client communicates with ObjectStorage clusters via ObjectStorage REST API.
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Http_Client
{
    const ZEND   = 1;
    const CURL   = 2;
    const SOCKET = 3;

    private static $client = array();

    private function __construct()
    {
    }

    /**
     * Validates a HTTP client adapter id. Valid adapter ids are: ObjectStorage_Http_Client::ZEND = 1, ObjectStorage_Http_Client::CURL = 2, ObjectStorage_Http_Client::SOCKET = 3
     *
     * @param int $adapterId
     */
    public static function validateAdapter($adapterId = 0)
    {
        if (in_array($adapterId, array(self::ZEND, self::SOCKET, self::CURL))) {
            return true;
        }
        return false;
    }

    /**
     * Returns an instance that implements ObjectStorage_Http_Adapter_Interface
     *
     * Options array accepts 'timeout' value.
     *
     * @param int $adapter
     * @param array $options
     *
     * @throws ObjectStorage_Exception
     */
    public static function factory($adapter = ObjectStorage_Http_Client::CURL, $options = array())
    {
        if (! isset(self::$client[$adapter])) {
            switch ($adapter) {
                case ObjectStorage_Http_Client::ZEND:
                    self::$client[$adapter] = new ObjectStorage_Http_Adapter_Zend($options);
                    break;
                case ObjectStorage_Http_Client::SOCKET:
                    self::$client[$adapter] = new ObjectStorage_Http_Adapter_Socket($options);
                    break;
                case ObjectStorage_Http_Client::CURL:
                    self::$client[$adapter] = new ObjectStorage_Http_Adapter_Curl($options);
                    break;
                default:
                    throw new ObjectStorage_Exception('Invalid HTTP client type.');
            }
        }

        if (! self::$client[$adapter] instanceof ObjectStorage_Http_Adapter_Interface) {
            throw new ObjectStorage_Exception('HTTP client must implement ObjectStorage_Http_Client_Interface.');
        }

        return self::$client[$adapter];
    }
}