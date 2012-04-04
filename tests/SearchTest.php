<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class SearchTest extends BaseTest
{
    protected static $containerToSearch = null;
    protected static $newContainerName = null;
    protected static $newObjectNames = array();
    protected static $newObjectBody = null;
    protected static $metaKey = null;
    protected static $newObjectMeta = null;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Removes cached authentication data if any
        self::$objectStorage->reloadAuthenticationData();

        // Grab a container to search
        $container = self::$objectStorage->with('/')->get();

        $containerCount = count($container->containers);

        if ($containerCount > 0) {
            $containerToSearch = $container->containers[$containerCount - rand(0, $containerCount - 1)];

            self::$containerToSearch = $containerToSearch->getPath();
        }

        self::$newContainerName = 'phpUnit_' . substr(md5(time()), 0, 7) . time();
        foreach (range(0, 5) as $idx) {
            self::$newObjectNames[] = self::$newContainerName . '/' . $idx . '.txt';
        }

        self::$metaKey = 'search-test';
        self::$newObjectMeta = 'doremipasolrasido';
        self::$newObjectBody = 'SoftLayer technologies. Object Stroage test file.';
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

        sleep(15);
    }

    public function testExistingContainerSearch()
    {
        echo 'Searching for "' . self::$containerToSearch . "\"\n";

        $containers = self::$objectStorage->with('')
                                ->setContext('search')
                                ->setFilter('type', 'container')
                                ->setFilter('q', self::$containerToSearch)
                                ->setMime('json')
                                ->get();

        $this->assertInstanceOf('ObjectStorage_Abstract', $containers);

        $containerCount = count($containers->containers);
        $mismatchCount = 0;

        echo 'Searching for existing container \'' . self::$containerToSearch . "\n";
        if ($containerCount > 0) {
            foreach ($containers->containers as $container) {
                if (stripos($container->getPath(), self::$containerToSearch) === false) {
                    $mismatchCount += 1;
                }
            }
            $this->assertEquals(0, $mismatchCount, 'There are ' . $mismatchCount . ' results that do NOT meet the criteria.');
        } else {
            $this->assertNotEquals($containerCount, 0, 'No match found for "' . self::$containerToSearch . '"');
        }
    }

    public function testNewContainerSearch()
    {
        echo 'Searching for "' . self::$newContainerName . "\"\n";

        $containers = self::$objectStorage->with('')
                                ->setContext('search')
                                ->setFilter('type', 'container')
                                ->setFilter('q', self::$containerToSearch)
                                ->setMime('json')
                                ->get();

        $this->assertInstanceOf('ObjectStorage_Abstract', $containers);

        $this->assertEquals(1, $containers->getContainerCount(), 'There are ' . $containers->getContainerCount() . ' containers when expecting only one.');
    }

    public function testNewObjectsMetaSearch()
    {
        echo 'Searching for "' . self::$newContainerName . "\"\n";

        $containers = self::$objectStorage->with('')
                                ->setContext('search')
                                ->setFilter('type', 'object')
                                ->setFilter('field', 'meta_' . self::$metaKey)
                                ->setFilter('q', self::$newObjectMeta)
                                ->setMime('json')
                                ->get();

        $this->assertInstanceOf('ObjectStorage_Abstract', $containers);

        $this->assertLessThan(count(self::$newObjectNames), $containers->getObjectCount(), 'There are ' . (int) $containers->getObjectCount() . ' objects when expecting larger than or equal to ' . count(self::$newObjectNames) . '.');
    }

    public function testDelete()
    {
        foreach (self::$newObjectNames as $name) {
            $result = self::$objectStorage->with($name)->delete();
            $this->assertTrue($result, 'Failed to delete ' . $name);
        }

        $result = self::$objectStorage->with(self::$newContainerName)->delete();

        $this->assertTrue($result, 'Failed to delete the test container ' . self::$newContainerName);

        sleep(15);
    }

    public function testDeletedObjectsMetaSearch()
    {
        echo 'Searching for "' . self::$newContainerName . "\"\n";

        $containers = self::$objectStorage->with('')
                            ->setContext('search')
                            ->setFilter('type', 'object')
                            ->setFilter('field', 'meta_' . self::$metaKey)
                            ->setFilter('q', self::$newObjectMeta)
                            ->setMime('json')
                            ->get();

        $this->assertInstanceOf('ObjectStorage_Abstract', $containers);

        $this->assertEquals(0, $containers->getObjectCount(), 'There are ' . (int) $containers->getObjectCount() . ' objects when expecting 0.');
    }
}