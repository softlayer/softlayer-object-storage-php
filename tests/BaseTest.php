<?php

$objectStorageDirectory = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR . 'ObjectStorage';
set_include_path(get_include_path() . PATH_SEPARATOR . $objectStorageDirectory);

require_once($directory . 'Util.php');

class BaseTest extends PHPUnit_Framework_TestCase
{
    protected static $objectStorage;

    protected static $host     = 'HOST';

    protected static $username = 'ACCOUNT:USERNAME';
    protected static $password = 'PASSWORD';

    protected static $tokenStore = null;

    public static function setUpBeforeClass()
    {
        self::$tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp'));
        $options = array('adapter' => ObjectStorage_Http_Client::SOCKET, 'timeout' => 10);
        ObjectStorage::setTokenStore(self::$tokenStore);
        $objectStorage01 = new ObjectStorage(self::$host, self::$username, self::$password, $options);

        self::$objectStorage = $objectStorage01;
    }
}