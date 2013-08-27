<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class AuthenticationTest extends BaseTest
{
    /**
     * testAuthenticationWithBadCredentials test sets a dummy token store.
     * We need to set the proper token store object for other test(s).
     */
    public function setUp()
    {
        self::$tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp'));
        ObjectStorage::setTokenStore(self::$tokenStore);
    }

    public function testAuthentication()
    {
        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        $authData = self::$objectStorage->getAuthenticationData();

        $this->assertObjectHasAttribute('objectStorageUrl', $authData);
        $this->assertObjectHasAttribute('authToken', $authData);
    }

    /**
     * @expectedException ObjectStorage_Exception_Http_Unauthorized
     */
    public function testAuthenticationWithBadCredentials()
    {
        $options = array('adapter' => ObjectStorage_Http_Client::SOCKET, 'timeout' => 10);
        $objectStorage = new ObjectStorage(self::$host, self::$username, 'bad-password-' . time(), $options);

        // Make sure we are not using cached auth data
        ObjectStorage::setTokenStore(new AuthenticationTest_TokenStore());

        $authData = $objectStorage->getAuthenticationData();
    }
}

class AuthenticationTest_TokenStore implements ObjectStorage_TokenStore_Interface
{
    public function get($key)
    {
        return null;
    }

    public function set($key, $data)
    {
        return true;
    }

    public function delete($key)
    {
        return true;
    }
}