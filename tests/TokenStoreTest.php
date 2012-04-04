<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class TokenStoreTest extends BaseTest
{
    protected static $key;
    protected static $testData;

    public static function setUpBeforeClass()
    {
        self::$key = 'phpunit_' . time();
        self::$testData = array('data' => md5('TEST VALUE'));
    }

    public function testFileStore()
    {
        $tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp/objectStorage'));

        $result = $tokenStore->set(self::$key, self::$testData);

        $this->assertTrue($result, 'Failed to add data.');

        $retrievedData = $tokenStore->get(self::$key);

        $this->assertEquals(self::$testData['data'], $retrievedData['data']);

        $resultDelete = $tokenStore->delete(self::$key, self::$testData);

        $this->assertTrue($resultDelete, 'Failed to delete data.');
    }

    public function testMemoryStore()
    {
        $tokenStore = ObjectStorage_TokenStore::factory('memory');

        self::$key = 'phpunit_' . time();
        self::$testData = array('data' => md5('TEST VALUE'));

        $result = $tokenStore->set(self::$key, self::$testData);

        $this->assertTrue($result, 'Failed to add data.');

        $retrievedData = $tokenStore->get(self::$key);

        $this->assertEquals(self::$testData['data'], $retrievedData['data']);

        $resultDelete = $tokenStore->delete(self::$key, self::$testData);

        $this->assertTrue($resultDelete, 'Failed to delete data.');
    }
}