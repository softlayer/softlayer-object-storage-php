<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class ContainerTest extends BaseTest
{
    protected static $newContainerName = null;
    protected static $newContainerMeta = null;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        // This container will be used to test creation/update and delete
        self::$newContainerName = 'phpUnit_' . substr(md5(time()), 0, 7) . time();

        self::$newContainerMeta = 'META DATA TO TEST';
    }

    public function testGetRoot()
    {
        $container = self::$objectStorage->with('/')->get();

        $this->assertInstanceOf('ObjectStorage_Abstract', $container);

        $headers = $container->getHeaders();

        if ($container->getContainerCount() > 0) {
            $this->assertEquals($container->getContainerCount(), count($container->containers),
            					'Container count in the header: ' . $container->getContainerCount() . ' and the actual count ' . count($container->containers) . ' do NOT match.');

            foreach ($container->containers as $container) {
                echo $container->getPath() . "\n";
            }
        }
    }

    public function testCreate()
    {
        $metaKey = 'Description';

        $newContainer = self::$objectStorage->with(self::$newContainerName)
                            ->setMeta($metaKey, self::$newContainerMeta)
                            ->create();

        $this->assertInstanceOf('ObjectStorage_Abstract', $newContainer, 'Failed to create new container: ' . self::$newContainerName);

        sleep(1);

        // Check integrity
        $refreshedContainer = $newContainer->reload();

        $metaValue = $refreshedContainer->getMeta($metaKey);

        $this->assertEquals(self::$newContainerMeta, $metaValue);
    }

    public function testDelete()
    {
        $result = self::$objectStorage->with(self::$newContainerName)->delete();

        $this->assertTrue($result, 'Failed to delete the test container ' . self::$newContainerName);
    }
}