<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class CdnContainerTest extends BaseTest
{
    protected static $newObjectName = null;
    protected static $newCdnContainerName = null;
    protected static $metaData = null;

    protected static $ttl;
    protected static $metaKey;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        $containerName = 'phpUnitCdn_' . substr(md5(time()), 0, 7) . time();
        // This container will be used to test creation/update and delete
        self::$newCdnContainerName = $containerName;
        self::$newObjectName = $containerName . '/object.txt';

        self::$ttl = 12345;
        self::$metaKey = 'Description';
        self::$metaData = 'META DATA TO TEST FOR CDN CONTAINER';
    }

    public function testCreate()
    {
        $newContainer = self::$objectStorage->with(self::$newCdnContainerName)
                            ->setMeta(self::$metaKey, self::$metaData)
                            ->enableCdn()
                            ->setTtl(self::$ttl)
                            ->create();

        $this->assertInstanceOf('ObjectStorage_Abstract', $newContainer, 'Failed to create new container: ' . self::$newCdnContainerName);

        $newObject = self::$objectStorage->with(self::$newObjectName)
                        ->setBody('Test file')
                        ->setMeta(self::$metaKey, self::$metaData)
                        ->create();

        echo 'New container: ' . self::$newCdnContainerName . "\n";
        echo 'New object: ' . self::$newObjectName . "\n";
        // Give enought time for indexing process
        sleep(10);
    }

    public function testGet()
    {
        $cdnContainer = self::$objectStorage->with(self::$newCdnContainerName)->setContext('cdn')->get();

        $metaValue = $cdnContainer->getMeta(self::$metaKey);

        $this->assertEquals(self::$metaData, $metaValue);

        $ttlValue = $cdnContainer->getHeader('x-cdn-ttl');

        $this->assertEquals(self::$ttl, $ttlValue);

        $publicReadMetaValue = $cdnContainer->getHeader('X-Container-Read');

        $this->assertEquals('.r:*', $publicReadMetaValue);
    }

    public function testPurgeCache()
    {
        $result = self::$objectStorage->with(self::$newObjectName)->setContext('cdn')->purgeCache();

        $this->assertTrue($result, 'Failed to purge cache for container ' . self::$newCdnContainerName);
    }

    public function testCdnUrls()
    {
        $cdnUrls = self::$objectStorage->with(self::$newObjectName)->setContext('cdn')->getCdnUrls();

        echo 'Cdn URL count = ' . count($cdnUrls) . "\n";
        $this->assertGreaterThan(0, count($cdnUrls), 'CDN URL cannot be found for ' . self::$newObjectName);
    }

    public function testDelete()
    {
        $result = self::$objectStorage->with(self::$newObjectName)->delete();
        $this->assertTrue($result, 'Failed to delete ' . self::$newObjectName);

        $result = self::$objectStorage->with(self::$newCdnContainerName)->delete();
        $this->assertTrue($result, 'Failed to delete ' . self::$newCdnContainerName);
    }
}