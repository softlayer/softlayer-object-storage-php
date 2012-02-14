<?php

// If you want to cache ObjectStorage authentication token:
$tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp/objectStorage'));
ObjectStorage::setTokenStore($tokenStore);

// If no adapter option is provided, CURL will be used.
$options = array('adapter' => ObjectStorage_Http_Client::SOCKET, 'timeout' => 10);
$objectStorage = new ObjectStorage($host, $username, $password, $options);

// Basic CRUD
$shallowContainer = $objectStorage->with('example_container');

$newContainer = $shallowContainer->create();

$updatedContainer = $newContainer->setMeta('Description', 'Adding a meta data')->update();

$reloadedContainer = $newContainer->get();

$result = $newContainer->delete();

// Creating an object is similar to that of container CRUD
$newObject = $objectStorage->with('example_container/object.txt')
                            ->setBody('test object')
                            ->setMeta('description', 'first test file')
                            ->create();

// If you wanted, you can do this all one line.
// Most functions return itself so you can chain method calls except delete method which returns a boolean value.
$result = $objectStorage->with('example_container')->create()->setMeta('Description', 'Adding a meta data')->update()->get()->delete();

// When you create a new container or an object, ObjectStorage_Abstract will return itself, not the newly created container or object.
// If you wish to reload the data from ObjectStorage cluster, use ObjectStorage_Abstract::get or ObjectStorage_Abstract::reload methods.
// It will fetch the container info from ObjectStorage and reload $newContainer object with it.
$newContainer = $objectStorage->with('example_container')->create()->reload();

// To create a CDN enabled container
$objectStorage->with('cdn_container')->enableCdn()->create();

// To update an existing container to a CDN enabled container
$objectStorage->with('another_container')->enableCdn()->setTtl(3600)->update();

// You can traverse container or objects like this:
$container = $objectStorage->with('another_container')->get();

if (count($container->objects) > 0) {
    foreach ($results->objects as $shallowObject) {
        $object = $shallowObject->get();

        echo $object->getUrl();
        echo $object->getBody();
    }
}

// Copy an object to another Object Storage
$objectStorage01 = new ObjectStorage($host01, $username01, $password01);
$objectStorage02 = new ObjectStorage($host02, $username02, $password02);

$object = $objectStorage01->with('container/object')->get();
$objectStorage02->create($object);

// Search
$objectOrContainer = $objectStorage05->with('')
                                    ->setContext('search')
                                    ->setFilter('type', 'container')
                                    ->setFilter('q', $searchKeyword)
                                    ->setMime('json')
                                    ->get();

// CDN purge cache. (In case you modified an object and need to refresh CDN cache.)
$objectStorage05->with('cdn_container/object')->purgeCache();

// CDN load cache
$objectStorage05->with('cdn_container/object')->loadCache();

// If you want to compress *text* files served via CDN.
$results = $objectStorage05->with('')->setContext('cdn')
                            ->setRequestHeader('X-CDN-COMPRESSION', 'true') // Set to "false" to turn off compression
                            ->setRequestHeader('X-CDN-COMPRESSION-MIME', 'text/plain,text/html,text/css,application/x-javascript,text/javascript')
                            ->update();

// If you want to add a custom CDN CNAME. (
// You can add a CNAME to a container level as well. To do so, pass an appropriate container name to with() method
// Keep in mind you can have only one custom CNAME per container
// To find your CNAME endpoint, use "dig" command on your existing CDN host. For example,
// $ dig 1234.http.dal05.cdn.softlayer.net
$results = $objectStorage05->with('')->setContext('cdn')
                            ->setRequestHeader('X-CDN-CNAME-Action', 'add') // Use "delete" if you wish to delete a CNAME
                            ->setRequestHeader('X-Cdn-CNAME', 'cdn.mysite.com')
                            ->update();

// ObjectStorage_Abstract has many properties but these three are the major componets
// * $objectStorage: holds reference to a ObjectStorage object (optional)
// * $request: HTTP request object is consisted of headers and body
// * $response: HTTP response object is consisted of headers and body

// You can access to HTTP request or response object using ObjectStorage_Abstract::getRequest or ObjectStorage_Abstract::getResponse.
// You can also use convenience getter and setters. These can help you avoid doing like this:
$container->getResponse()->setMeta('description', 'example meta');
$container->getRequest()->getBody();

// and you can do this instead:
$container->setMeta('description', 'example meta');
$container->getBody();

// Simple idea is that you *set* data to HTTP request and *get* data from HTTP response.

// Sets data to HTTP request object
// * ObjectStorage_Abstract::setHeader
// * ObjectStorage_Abstract::setHeasers
// * ObjectStorage_Abstract::setMeta
// * ObjectStorage_Abstract::setBody

// Gets data from HTTP response object
// * ObjectStorage_Abstract::getHeader
// * ObjectStorage_Abstract::getHeaders
// * ObjectStorage_Abstract::getMeta
// * ObjectStorage_Abstract::getBody