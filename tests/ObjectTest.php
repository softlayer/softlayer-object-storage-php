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
    protected static $copiedFileName = null;
    protected static $localFileName = null;


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

        self::$copiedFileName = self::$newContainerName . '/copied-file.dummy';
        self::$localFileName = '/tmp/object-storage-' . time() . '.dummy.file';
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

        sleep(1);
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

    public function testCreateFromFile()
    {
        $result = @file_put_contents(self::$localFileName, str_repeat(rand(0,9), 1024*1024));
        sleep(1);

        if ($result == false) {
            echo 'WARN: failed to create a temporary file for upload test. Path: ' . self::$localFileName . "\n";
        } else {
            $object = self::$objectStorage->with(self::$copiedFileName)
                        ->setLocalFile(self::$localFileName)
                        ->create();

            $this->assertInstanceOf('ObjectStorage_Abstract', $object, 'Failed to create new object: ' . $name);
        }

        $copiedObject = self::$objectStorage->with(self::$copiedFileName)->getInfo();

        $fileSize = filesize(self::$localFileName);
        $this->assertEquals($copiedObject->getHeader('content-length'), $fileSize, 'Copied object size (' . $copiedObject->getHeader('content-length') . ') and the local file size (' . $fileSize. ') do not match.');

    }

    public function testDelete()
    {
        foreach (self::$newObjectNames as $name) {
            $result = self::$objectStorage->with($name)->delete();
            $this->assertTrue($result, 'Failed to delete ' . $name);
        }

        self::$objectStorage->with(self::$copiedFileName)->delete();

        $result = self::$objectStorage->with(self::$newContainerName)->delete();

        $this->assertTrue($result, 'Failed to delete the test container ' . self::$newContainerName);

        @unlink(self::$localFileName);
    }

    /**
     * @expectedException ObjectStorage_Exception_Http_NotFound
     */
    public function testDeleteNonExistingObject()
    {
        $result = self::$objectStorage->with('i-do-not-exist-' . time())->delete();
    }
}