<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class AuthenticationTest extends BaseTest
{
    public function testAuthentication()
    {
        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        $authData = self::$objectStorage->getAuthenticationData();

        $this->assertObjectHasAttribute('objectStorageUrl', $authData);
        $this->assertObjectHasAttribute('authToken', $authData);
    }
}