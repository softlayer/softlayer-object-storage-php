<?php
/**
 * This class represents a ObjectStorage object.
 *
 * @see ObjectStorage_Abstract
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Object extends ObjectStorage_Abstract
{
    public static $skipOverwriteHeaders = array('ETAG', 'ACCEPT-RANGES', 'LAST-MODIFIED', 'DATE', 'CONNECTION', 'CONTENT-LENGTH');

    protected function getMetaPrefix()
    {
        return 'X-object-meta-';
    }

    public function __toString()
    {
        return 'ObjectStorage Object';
    }

    /**
     * (non-PHPdoc)
     * @see ObjectStorage_Abstract::create()
     */
    public function create()
    {
        if ($this->request->getHeader('Content-Length') == null || $this->request->getHeader('Content-Length') == 0) {
            $this->request->setHeader('Content-Length', strlen($this->request->getBody()));
        }

        return parent::create();
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
        $this->request->setHeader('Content-Length', strlen($this->request->getBody()));

        return parent::update();
    }

    /**
     * Removes CDN cache. This method is only applicable to objects in CDN enabled containers
     *
     * @return bool
     */
    public function purgeCache()
    {
        $this->setContext(self::CONTEXT_CDN);
        try {
            $this->request->setHeader('X-Cdn-Purge', 'true');
            $this->objectStorage->update($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            $this->objectStorage->update($this);
        }
        return true;
    }

    /**
     * Load object to CDN. This method is only applicable to objects in CDN enabled containers
     *
     * @return bool
     */
    public function loadCache()
    {
        $this->setContext(self::CONTEXT_CDN);
        try {
            $this->request->setHeader('X-Cdn-Load', 'true');
            $this->objectStorage->update($this);
        } catch (Exception $e) {
            $this->objectStorage->reloadAuthenticationData();
            $this->objectStorage->update($this);
        }
        return true;
    }
}