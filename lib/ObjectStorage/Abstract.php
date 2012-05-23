<?php

/**
 * Abstract class for ObjectStorage_Container and ObjectStorage_Object.
 *
 * ObjectStorage_Abstract class represents a ObjectStorage container or an object.
 * With this class, you can easily create/retrieve/update/delete ObjectStorage containers and objects.
 *
 * ObjectStorage_Abstract has many properties but these three are the major componets
 *
 * * $objectStorage: holds reference to a ObjectStorage object (optional)
 * * $request: HTTP request object is consisted of headers and body
 * * $response: HTTP response object is consisted of headers and body
 *
 * CRUD operation of a container
 * <code>
 * $objectStorageDallas = new ObjectStorage($objectStorageHost, $username, $password, $options);
 *
 * $newContainer = $objectStorageDallas->with('example_container')->create();
 *
 * $updatedContainer = $newContainer->setMeta('Description', 'Adding a meta data')->update();
 *
 * $reloadedContainer = $newContainer->get();
 *
 * $result = $newContainer->delete();
 *
 * // If you wanted, you can do this all one line.
 * // Most functions return itself so you can chain method calls except detele method which returns a boolean value.
 * $result = $objectStorageDallas05->with('example_container')->create()->setMeta('Description', 'Adding a meta data')->update()->get()->delete();
 *
 * // When you create a new container or an object, ObjectStorage_Abstract will return itself, not the newly created conainer or object.
 * // If you wish to reload the the data from ObjectStorage cluster, use ObjectStorage_Abstract::get or ObjectStorage_Abstract::reload methods.
 * $newContainer = $objectStorageDallas->with('example_container')->create();
 *
 * // It will fetch the container info from ObjectStorage and reload $newContainer object with it.
 * $newContainer->reload();
 * </code>
 *
 * ObjectStorage_Abstract tries to postpone the actual interaction with ObjectStorage as late as it can.
 * Authentication to ObjectStorage or any CRUD operation happens when you invoke these method:
 * * create
 * * get
 * * getInfo (Equivalent to HEAD request)
 * * update
 * * delete
 * * getCdnUrls
 * * purgeCache (only applicable to a ObjectStorage_Object in a public container)
 * * loadCache (only applicable to a ObjectStorage_Object in a public container)
 *
 * In order to make objects available via CDN, you will need to make a container public. See the example below:
 * <code>
 * // To create a CDN enabled container
 * $objectStorageDallas->with('cdn_container')->enableCdn()->create();
 *
 * // To update an existing container to a CDN enabled container
 * $objectStorageDallas->with('another_container')->enableCdn()->update();
 *
 * // Likewise, you can change a container private this way.
 * $objectStorageDallas->with('another_container')->disableCdn()->update();
 * </code>
 *
 * You can iterate through container or object using $containers or $objects property
 * <code>
 * if (count($container->objects) > 0) {
 *   foreach ($results->objects as $shallowObject) {
 *       $object = $shallowObject->get();
 *
 *       echo $object->getUrl();
 *       echo $object->getResponse()->getBody();
 *   }
 * }
 *
 * You can copy a container or an object to another ObjectStorage cluster this way:
 * <code>
 * $objectStorageDallas = new ObjectStorage($objectStorageHost01, $username01, $password01, $options);
 * $objectStorageSeattle = new ObjectStorage($objectStorageHost02, $username02, $password02, $options);
 *
 * $object = $objectStorageDallas->with('path/object')->get();
 * $objectStorageSeattle->create($object);
 * </code>
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
abstract class ObjectStorage_Abstract
{
    public $containers = array();
    public $objects    = array();
    public $limit = 0;
    public $marker = '';
    public $searchFilters = array();
    public $queryString = array();

    protected $path;
    protected $mime;

    protected $request;
    protected $response;

    protected $objectStorage;
    protected $context = '';
    protected $containerCount = null;
    protected $objectCount = null;

    protected static $httpClient;

    const CONTEXT_CDN    = 'CDN';
    const CONTEXT_SEARCH = 'SEARCH';

    /**
     * Constructor.
     *
     * @param string $path ObjectStorage path delimited by slash.
     * @param ObjectStorage $objectStorage
     */
    public function __construct($path, ObjectStorage $objectStorage)
    {
        $this->path    = $path;
        $this->objectStorage   = $objectStorage;

        $this->request = new ObjectStorage_Http_Request();
        $this->response = new ObjectStorage_Http_Response();
    }

    /**
     * Returns the ObjectStorage path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns response HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Sets a meta data
     * "Meta data" is an optional custom header and it is a part of the ObjectStorage response headers
     *
     * @param string $metaKey
     * @param string $value
     *
     * @return ObjectStorage_Abstract
     */
    public function setMeta($metaKey = '', $value = '')
    {
        $this->request->setHeader($this->getMetaPrefix() . strtolower($metaKey), (string) $value);
        return $this;
    }

    /**
     * Gets a meta data by key in the response
     *
     * @param string $metaKey
     *
     * @return string
     */
    public function getMeta($metaKey = '')
    {
        return $this->response->getHeader($this->getMetaPrefix() . strtolower($metaKey));
    }

    /**
     * Sets a meta data
     *
     * @param string $metaKey
     * @param string $value
     *
     * @return ObjectStorage_Abstract
     */
    public function setAccountMeta($metaKey = '', $value = '')
    {
        $this->request->setHeader('X-Account-Meta-' . strtolower($metaKey), (string) $value);
        return $this;
    }

    /**
     * Sets an array of meta data
     *
     * @param array $metaData
     *
     * @return ObjectStorage_Abstract
     */
    public function setMetaList($metaData = array())
    {
        if (is_array($metaData) && count($metaData) > 0) {
            foreach ($metaData as $key => $value) {
                $this->setMeta($key, $value);
            }
        }
        return $this;
    }

    /**
     * Sets a request header
     * Use this when you need to add special request headers such as X-CDN-CNAME and so on.
     *
     * @param string $headerKey
     * @param string $value
     *
     * @return ObjectStorage_Abstract
     */
    public function setHeader($headerKey = '', $value = '')
    {
        $this->request->setHeader(ucfirst(strtolower($headerKey)), (string) $value);
        return $this;
    }

    /**
     * Sets an array of ObjectStorage headers
     *
     * @param array $headerData
     *
     * @return ObjectStorage_Abstract
     */
    public function setHeaders($headerData = array())
    {
        if (is_array($headerData) && count($headerData) > 0) {
            foreach ($headerData as $key => $value) {
                $this->setHeader($key, $value);
            }
        }
        return $this;
    }

    /**
     * Gets a response header by key
     *
     * @param string $headerKey
     *
     * @return string
     */
    public function getHeader($headerKey = '')
    {
        return $this->response->getHeader($headerKey);
    }

    /**
     * Gets an array of response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    /**
     * Sets request body
     *
     * @param mixed $body
     *
     * @return ObjectStorage_Abstract
     */
    public function setBody($body)
    {
        $this->request->setBody($body);
        return $this;
    }

    /**
     * Gets body data from response object
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->response->getBody();
    }

    /**
     * Return ObjectStorage_Http_Request object
     *
     * @return ObjectStorage_Http_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns ObjectStorage_Http_Response object
     *
     * @return ObjectStorage_Http_Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets response object
     *
     * @param ObjectStorage_Http_Response $response
     */
    public function setResponse(ObjectStorage_Http_Response $response)
    {
        if ($response->getHeader('X-cdn-enabled-item-count') != null) {
            $this->containerCount = $response->getHeader('X-cdn-enabled-item-count');
        } else if ($response->getHeader('X-account-container-count') != null) {
            $this->containerCount = $response->getHeader('X-account-container-count');
        }

        if ($response->getHeader('X-container-object-count') != null) {
            $this->objectCount = $response->getHeader('X-container-object-count');
        }
        $this->response = $response;
    }

    /**
     * Sets a ObjectStorage response format. Available values are: json, xml, text
     *
     * @param string $mime
     *
     * @return ObjectStorage_Abstract
     */
    public function setMime($mime)
    {
        switch (strtoupper($mime)) {
            case ObjectStorage::MIME_JSON:
                $this->setHeader('Accept', 'application/json; charset=utf-8');
                break;
            case ObjectStorage::MIME_XML:
                $this->setHeader('Accept', 'application/xml; charset=utf-8');
                break;
            case ObjectStorage::MIME_TEXT:
                $this->setHeader('Accept', 'plain/text; charset=utf-8');
                break;
            default:
                throw new ObjectStorage_Exception(null, 'Invalid MIME type is provided.');
        }

        $this->mime = strtoupper($mime);

        return $this;
    }

    /**
     * Sets a TTL value for a container or an object
     *
     * @param int $ttlValue
     *
     * @return ObjectStorage_Abstract
     */
    public function setTtl($ttlValue = 3600)
    {
        $this->setHeader('X-Cdn-Ttl', (int) $ttlValue);
        return $this;
    }

    /**
     * Gets a TTL value for a container or an object from HTTP response
     *
     * @return int
     */
    public function getTtl()
    {
        $this->getHeader('X-Cdn-Ttl');
    }

    /**
     * Sets a search filter. This method is used when you are working in "search" context.
     *
     * @param string $key
     * @param string $value
     *
     * @throws ObjectStorage_Exception
     *
     * @return ObjectStorage_Abstract
     */
    public function setFilter($key, $value)
    {
        if ($this->context != ObjectStorage_Abstract::CONTEXT_SEARCH) {
            throw new ObjectStorage_Exception('Filter can be set within the "search" contenxt.');
        }

        // @todo Validate $key
        $this->searchFilters[$key] = (string) $value;

        return $this;
    }

    /**
     * Sets a query parameter. This method is used to set a URL parameter.
     *
     * @param string $key
     * @param string $value
     *
     * @return ObjectStorage_Abstract
     */
    public function setParam($key, $value)
    {
        // @todo Validate $key
        $this->queryString[$key] = (string) $value;

        return $this;
    }

    /**
     * Returns the ObjectStorage context value
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the ObjectStorage context. Valid context values are: cdn, search
     *
     * @param string $name
     * @return ObjectStorage_Abstract
     */
    public function setContext($name = '')
    {
        $name = strtoupper($name);
        if (in_array($name, array(self::CONTEXT_CDN, self::CONTEXT_SEARCH))) {
            $this->context = $name;
            $this->setHeader('X-Context', $name);
        }

        return $this;
    }

    /**
     * Removes the ObjectStorage context if any. It reset the context back to the default ObjectStorage context.
     *
     * @return ObjectStorage_Abstract
     */
    public function removeContext()
    {
        $this->context = '';
        $this->request->deleteHeader('X-Context');
        return $this;
    }

    /**
     * Returns the full ObjectStorage URL
     *
     * @return string
     */
    public function getUrl()
    {
        $url = $this->objectStorage->getUrl($this);

        $queryString = array();

        if ($this->marker != '') {
            $queryString[] = 'marker=' . $this->marker;
        }

        if (count($this->searchFilters) > 0) {
            foreach ($this->searchFilters as $key => $value) {
                $queryString[] = $key . '=' . $value;
            }
        }

        if (count($this->queryString) > 0) {
            foreach ($this->queryString as $key => $value) {
                $queryString[] = $key . '=' . $value;
            }
        }

        if (count($queryString) > 0) {
            $url .= '?' . implode('&', $queryString);
        }

        return $url;
    }

    /**
     * Returns total number of containers
     *
     * @return array
     */
    public function getContainerCount()
    {
        return $this->containerCount;
    }

    /**
     * Returns total number of objects within the current container
     *
     * @return array
     */
    public function getObjectCount()
    {
        return $this->objectCount;
    }

    /**
     * Returns all CDN URLs
     *
     * @return array
     */
    public function getCdnUrls()
    {
        try {
            return $this->objectStorage->getCdnUrls($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->getCdnUrls($this);
        }
    }

    /**
     * Retrieves the container or object data from ObjectStorage
     * $limit and $marker will not be taken into consideration when dealing with an object.
     *
     * @param int $limit
     * @param string $marker
     *
     * @return ObjectStorage_Abstract
     */
    public function get($limit = 100, $marker = '')
    {
        $this->limit = $limit;
        $this->marker = $marker;

        try {
            return $this->objectStorage->get($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->get($this);
        }
    }

    /**
     * Returns the header and meta data of a container or an object.
     *
     * @return ObjectStorage_Abstract
     */
    public function getInfo()
    {
        try {
            return $this->objectStorage->get($this, false);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->get($this, false);
        }
    }

    /**
     * Creates a container or an object on ObjectStorage
     *
     * @return ObjectStorage_Abstract
     */
    public function create()
    {
        try {
            return $this->objectStorage->create($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->create($this);
        }
    }

    /**
     * Updates a container or an object on ObjectStorage
     *
     * @return ObjectStorage_Abstract
     */
    public function update()
    {
        try {
            return $this->objectStorage->update($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->update($this);
        }
    }

    /**
     * Deletes a container or an object on ObjectStorage
     *
     * @return bool
     */
    public function delete()
    {
        try {
            return $this->objectStorage->delete($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            return $this->objectStorage->delete($this);
        }
    }

    /**
     * Reloads the current object with the newly retrieved data from ObjectStorage.
     * Synonym of ObjectStorage_Abstract::get method
     *
     * @return ObjectStorage_Abstract
     */
    public function reload()
    {
        return $this->get();
    }
}