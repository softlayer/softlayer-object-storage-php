<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class ObjectTest extends BaseTest
{
    protected static $newContainerName = null;
    protected static $newObjectNames = array();
    protected static $newObjectBody = null;
    protected static $newObjectMeta = null;
    protected static $metaKey = null;
    protected static $wackyObjectName = null;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        // This container will be used to test creation/update and delete
        self::$newContainerName = 'phpUnit_' . substr(md5(time()), 0, 7) . time();
        foreach (range(0, 5) as $idx) {
            self::$newObjectNames[] = self::$newContainerName . '/' . $idx . '.txt';
        }

        self::$metaKey = 'Description';
        self::$newObjectMeta = 'META DATA TO TEST';
        self::$newObjectBody = 'This is a test file created by PHPUnit test. It should be removed shortly. If not, something\'s gone wrong.';
    }

    public function testCreate()
    {
        $newContainer = self::$objectStorage->with(self::$newContainerName)->create();
        $this->assertInstanceOf('ObjectStorage_Abstract', $newContainer, 'Failed to create new container: ' . self::$newContainerName);

        foreach (self::$newObjectNames as $name) {
            $object = self::$objectStorage->with($name)
                        ->setMeta(self::$metaKey, self::$newObjectMeta)
                        ->setBody(self::$newObjectBody)
                        ->create();
            $this->assertInstanceOf('ObjectStorage_Abstract', $object, 'Failed to create new object: ' . $name);
        }

        sleep(10);
    }

    public function testGet()
    {
        $container = self::$objectStorage->with(self::$newContainerName)->get();

        $this->assertEquals($container->getObjectCount(), count(self::$newObjectNames), 'Object count must be ' . count(self::$newObjectNames));

        if ($container->getObjectCount() > 0) {
            foreach ($container->objects as $shallowObject) {
                $object = $shallowObject->get();

                $this->assertEquals(self::$newObjectMeta, $object->getHeader('X-object-meta-' . self::$metaKey));
                $this->assertEquals(self::$newObjectBody, $object->getBody());
            }
        }
    }

    public function testDelete()
    {
        foreach (self::$newObjectNames as $name) {
            $result = self::$objectStorage->with($name)->delete();
            $this->assertTrue($result, 'Failed to delete ' . $name);
        }

        $result = self::$objectStorage->with(self::$newContainerName)->delete();

        $this->assertTrue($result, 'Failed to delete the test container ' . self::$newContainerName);
    }
}