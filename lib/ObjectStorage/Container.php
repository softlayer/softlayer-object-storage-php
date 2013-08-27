<?php
/**
 * This class represents a ObjectStorage container.
 *
 * @see ObjectStorage_Abstract
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Container extends ObjectStorage_Abstract
{
    public static $skipOverwriteHeaders = array('ETAG', 'ACCEPT-RANGES', 'LAST-MODIFIED', 'DATE', 'CONNECTION');

    protected function getMetaPrefix()
    {
        return 'X-container-meta-';
    }

    /**
     * Enables CDN. This method sets a "public read" meta data.
     * You have to call ObjectStorage_Abstract::create or ObjectStorage_Abstract_update method in order for this to take affect.
     *
     * @return ObjectStorage_Container
     */
    public function enableCdn()
    {
        $this->request->setHeader('X-CONTAINER-READ', '.r:*');

        return $this;
    }

    /**
     * Disables CDN. This method removes a "public read" meta data.
     * You have to call ObjectStorage_Abstract::create or ObjectStorage_Abstract_update method in order for this to take affect.
     *
     * @return ObjectStorage_Container
     */
    public function disableCdn()
    {
        $this->request->setHeader('X-CONTAINER-READ', '');

        return $this;
    }

    /**
     * Adds CNAME. This method sets a CNAME on the container.
     * You have to call ObjectStorage_Abstract::create or ObjectStorage_Abstract_update method in order for this to take affect.
     *
     * @return ObjectStorage_Container
     */
    public function addCname($cnameUrl, $type='HTTP')
    {
        $this->setContext(self::CONTEXT_CDN);
        $this->request->setHeader('X-CDN-CNAME', $cnameUrl);
        $this->request->setHeader('X-CDN-CNAME-TYPE', $type);
        $this->request->setHeader('X-CDN-CNAME-ACTION', 'add');

        return $this;
    }

    /**
     * Removes CNAME. This method removes the CNAME from the container.
     * You have to call ObjectStorage_Abstract::create or ObjectStorage_Abstract_update method in order for this to take affect.
     *
     * @return ObjectStorage_Container
     */
    public function removeCname($cnameUrl)
    {
        $this->setContext(self::CONTEXT_CDN);
        $this->request->setHeader('X-CDN-CNAME', $cnameUrl);
        $this->request->setHeader('X-CDN-CNAME-ACTION', 'delete');

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Abstract::update()
     */
    public function update()
    {
        $headers = $this->request->getHeaders();
        if (count($headers) > 0) {
            foreach ($headers as $key => $value) {
                if (in_array(strtoupper($key), self::$skipOverwriteHeaders)) {
                    $this->request->deleteHeader($key);
                }
            }
        }
        return parent::update();
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Abstract::get()
     */
    public function get($limit = 100, $marker = '')
    {
        parent::get($limit, $marker);

        if ($this->response->getBody() == '') {
            return $this;
        }

        switch (strtoupper($this->mime)) {
            case ObjectStorage::MIME_JSON:
                $objects = json_decode($this->response->getBody());

                if (count($objects) > 0) {
                    foreach ($objects as $object) {
                        if ($this->context == ObjectStorage_Abstract::CONTEXT_SEARCH) {
                            $path = $object->type == 'container' ? $object->container : $object->container . '/' . $object->name;
                        } else {
                            $path = $this->path . '/' . (isset($object->name) ? $object->name : (isset($object->subdir) ? $object->subdir : ''));
                        }
                        $this->appendData($this->objectStorage->with($path));
                    }
                }
                break;
            case ObjectStorage::MIME_XML:
                // @todo implement this
                $data = simplexml_load_string($this->response->getBody());

                $loopData = count($data->object) > 0 ? $data->object : (count($data->container) > 0 ? $data->container : array());
                if (count($loopData) > 0) {
                    if ($this->context == ObjectStorage_Abstract::CONTEXT_SEARCH) {
                        foreach ($loopData as $object) {
                            $path = (string) $object->type == 'container' ? $object->container : $object->container . '/' . $object->name;
                            $this->appendData($this->objectStorage->with($path));
                        }
                    } else {
                        foreach ($loopData as $object) {
                            $this->appendData($this->objectStorage->with($this->path . '/' . (string) $object->name));
                        }
                    }
                }
                break;
            default:
                // plain/text
                $objects = explode("\n", trim($this->response->getBody(), "\n"));

                if (count($objects) > 0) {
                    $path = ($this->path == '') ? '' : trim($this->path, '/') . '/';
                    foreach ($objects as $object) {
                        $this->appendData($this->objectStorage->with($path . $object));
                    }
                }
        }

        if ($this->context == ObjectStorage_Abstract::CONTEXT_SEARCH) {
            $this->containerCount = count($this->containers);
            $this->objectCount = count($this->objects);
        }

        return $this;
    }

    protected function appendData(ObjectStorage_Abstract $objectStorageObject)
    {
        if ($objectStorageObject instanceof ObjectStorage_Container) {
            $this->containers[] = $objectStorageObject;
        } else {
            $this->objects[] = $objectStorageObject;
        }
    }

    public function __toString()
    {
        return 'ObjectStorage Container';
    }
}