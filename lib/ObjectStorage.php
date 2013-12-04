<?php

/**
 * ObjectStorage object which represents a ObjectStorage cluster.
 *
 * @see ObjectStorage_Abstract
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage
{
    protected $httpClient;
    protected $httpClientAdapterTimeout = 10;
    protected $httpClientAdapterIdendifier;

    protected $objectStorageHost;
    protected $username;
    protected $password;
    protected $objectStorageAccountName;

    protected $objectStorageAuthData;

    protected static $tokenStore;

    const MIME_JSON = 'JSON';
    const MIME_XML  = 'XML';
    const MIME_TEXT  = 'TEXT';

    /**
     * ObjectStorage constructor
     *
     * Instantiates a ObjectStorage object. All parameters are required except $options array.
     * With $options array, you can specify HTTP client adapter type. By default, CURL adapter will be used.
     *
     * You can instantiate multiple ObjectStorage objects if you are dealing with a ObjectStorage cluster.
     *
     * <code>
     * $options = array('adapter' => ObjectStorage_Http_Client::CURL, 'timeout' => 10);
     *
     * $objectStorageDallas = new ObjectStorage($objectStorageHost01, $username01, $password01, $options);
     *
     * $objectStorageSeattle = new ObjectStorage($objectStorageHost02, $username02, $password02, $options);
     * </code>
     *
     * @param string $objectStorageHost
     * @param string $username
     * @param string $password
     * @param array $options
     *
     * @throws ObjectStorage_Exception_Authentication
     */
    public function __construct($objectStorageHost = null, $username = null, $password = null, $options = array())
    {
        if ($objectStorageHost == null || $username == null || $password == null) {
            throw new ObjectStorage_Exception_Authentication('You must provide ObjectStorage host, username and password.');
        }

        $this->objectStorageHost = $objectStorageHost;
        $this->username = $username;
        $this->password = $password;

        if (isset($options['adapter']) && ObjectStorage_Http_Client::validateAdapter($options['adapter'])) {
            $this->httpClientAdapterIdendifier = $options['adapter'];
        } else {
            $this->httpClientAdapterIdendifier = ObjectStorage_Http_Client::CURL;
        }

        if (isset($options['timeout'])) {
            $this->httpClientAdapterTimeout    = $options['timeout'];
        }
    }

    /**
     * Returns an object with ObjectStorage URL and authentication token
     *
     * @return ObjectStorage_AuthData
     */
    public function getAuthenticationData()
    {
        if ($this->objectStorageAuthData != null) {
            return $this->objectStorageAuthData;
        }

        $cacheKey = $this->getAuthenticationCacheKey();
        $authData = self::getTokenStore()->get($cacheKey);

        if ($authData == null) {
            $response = $this->authenticate();

            $authData = new ObjectStorage_AuthData();
            $authData->objectStorageUrl = $response->getHeader('X-storage-url');
            $authData->authToken = $response->getHeader('X-auth-token');

            $this->objectStorageAuthData = $authData;

            self::getTokenStore()->set($cacheKey, $authData);
        } else {
            $this->objectStorageAuthData = $authData;
        }

        return $this->objectStorageAuthData;
    }

    protected function getAuthenticationCacheKey()
    {
        return $this->objectStorageHost . $this->username;
    }

    /**
     * Sets the persistent ObjectStorage authentication token storage
     *
     * <code>
     * // ObjectStorage auth token can be reused in subsequent requests instead of each request attempts to authenticate again and again.
     * $tokenStore = ObjectStorage_TokenStore::factory('file', array('ttl' => 3600, 'path' => '/tmp/objectStorage'));
     * ObjectStorage::setTokenStore($tokenStore);
     *
     * $options = array('adapter' => ObjectStorage_Http_Client::CURL, 'timeout' => 10);
     * $objectStorageDallas = new ObjectStorage($objectStorageHost, $username, $password, $options);
     * </code>
     *
     * @param ObjectStorage_TokenStore_Interface $tokenStore
     */
    public static function setTokenStore(ObjectStorage_TokenStore_Interface $tokenStore)
    {
        self::$tokenStore = $tokenStore;
    }

    protected static function getTokenStore()
    {
        if (! self::$tokenStore instanceof ObjectStorage_TokenStore_Interface) {
            self::$tokenStore = ObjectStorage_TokenStore::factory('memory');
        }

        return self::$tokenStore;
    }

    /**
     * Returns authentication token and ObjectStorage account URL
     *
     * @return ObjectStorage_Http_Response
     */
    protected function authenticate()
    {
        $client = $this->getHttpClient();

        $client->setUri($this->objectStorageHost . '/auth/v1.0');
        $client->setHeaders('X-Auth-User', $this->username);
        $client->setHeaders('X-Auth-Key', $this->password);
        $client->setMethod('GET');

        try {
            $response = $client->request();
        } catch (Exception $e) {
            throw new ObjectStorage_Exception_Authentication($e->getMessage());
        }

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            return $response;
        } else {
            throw ObjectStorage_Exception_Http::factory(null, $response->getStatusCode());
        }
    }

    /**
     * ObjectStorage object stores an authentication token in a Token Store if configured.
     * If you changed your ObjectStorage password and you need to delete the old token, use this method to remove the cached token.
     *
     * @return boolean
     */
    public function reloadAuthenticationData()
    {
        $cacheKey = $this->getAuthenticationCacheKey();

        $response = $this->authenticate();

        $authData = new stdClass();
        $authData->objectStorageUrl = $response->getHeader('X-storage-url');
        $authData->authToken = $response->getHeader('X-auth-token');

        $this->objectStorageAuthData = $authData;

        self::getTokenStore()->set($cacheKey, $authData);

        return true;
    }

    protected function getHttpClient()
    {
        if (! isset($this->httpClient)) {
            $this->httpClient = ObjectStorage_Http_Client::factory($this->httpClientAdapterIdendifier, array('timeout' => $this->httpClientAdapterTimeout));
        }
        // Remove previous request headers and other trails
        $this->httpClient->reset();
        return $this->httpClient;
    }

    /**
     * Returns an instance of a ObjectStorage_Abstract sub-class which can be ObjectStorage_Container or ObjectStorage_Object.
     * A ObjectStorage_Abstract instance returned from this method will have the link to the ObjectStorage object.
     *
     * @see ObjectStorage_Abstract
     *
     * @param string $path
     *
     * @return ObjectStorage_Abstract
     */
    public function with($path)
    {
        return $this->getResponseWrapper($path);
    }

    protected function getResponseWrapper($path)
    {
        $path = trim($path, '/');
        list($container, $object) = $this->parseUri($path);

        if ($object != null) {
            return new ObjectStorage_Object($path, $this);
        } else {
            return new ObjectStorage_Container($path, $this);
        }
    }

    protected function parseUri($path)
    {
        $fragments = explode('/', $path);

        $fragCount = count($fragments);
        if ($fragCount >= 1) {
            $container = array_shift($fragments);
            return array($container, implode('/', $fragments));
        } else if ($fragCount == 1) {
            return array($fragments[0], null);
        } else {
            return array('/', null);
        }
    }

    /**
     * Returns a ObjectStorage container or an object.
     * You will less likely use this method directly from ObjectStorage object. This method is used by ObjectStorage_Abstract object.
     *
     * @param ObjectStorage_Abstract $objectStorageObject
     * @param bool $retrieveBody
     *
     * @return ObjectStorage_Abstract
     */
    public function get(ObjectStorage_Abstract $objectStorageObject, $retrieveBody = true)
    {
        $authData = $this->getAuthenticationData();

        $client = $this->getHttpClient();
        $client->setHeaders('X-Auth-Token', $authData->authToken);
        $httpMethod = $retrieveBody == true ? 'GET' : 'HEAD';

        $client->setMethod($httpMethod);

        $uri = $authData->objectStorageUrl . '/' . rawurlencode(ltrim($objectStorageObject->getPath(), '/'));

        $queryParams = array();
        if ($objectStorageObject->marker != '') {
            $queryParams[] = 'marker=' . urlencode($objectStorageObject->marker);
        }

        if (count($objectStorageObject->queryString) > 0) {
            foreach ($objectStorageObject->queryString as $key => $value) {
                $queryParams[] = $key . '=' . urlencode($value);
            }
        }

        if ($objectStorageObject->limit) {
            $queryParams[] = 'limit=' . urlencode($objectStorageObject->limit);
        }

        if ($objectStorageObject->getContext() == ObjectStorage_Abstract::CONTEXT_SEARCH && count($objectStorageObject->searchFilters) > 0) {
            foreach ($objectStorageObject->searchFilters as $key => $val) {
                $queryParams[] = urlencode($key) . '=' . urlencode($val);
            }
        }

        if (count($queryParams) > 0) {
            $uri .= '?' . implode('&', $queryParams);
        }

        $client->setUri($uri);

        $headers = $objectStorageObject->getRequest()->getHeaders();

        if (count($headers) > 0) {
            foreach ($headers as $key => $value) {
                $client->setHeaders($key, $value);
            }
        }

        $response = $client->request();

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            $objectStorageObject->setResponse($response);
            return $objectStorageObject;
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to retrieve "' . $objectStorageObject->getPath() . '".', $response->getStatusCode());
        }
    }

    /**
     * Returns ObjectStorage user list and cluster information in JSON format.
     * This will only work with users with admin privileges.
     *
     * @return string json data
     *
     * @throws ObjectStorage_Exception_Http
     */
    public function getClusterInfo()
    {
        $client = $this->getHttpClient();
        $client->setMethod('GET');

        list($account, $username) = explode(':', $this->username);

        $client->setUri($this->objectStorageHost . '/auth/v2/' . $account);
        $client->setHeaders('X-Auth-Admin-User', $this->username);
        $client->setHeaders('X-Auth-Admin-Key', $this->password);

        try {
            $response = $client->request();
        } catch (Exception $e) {
            throw ObjectStorage_Exception_Http::factory('Failed to retrieve users.');
        }

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            return $response->getBody();
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to retrieve users.', $response->getStatusCode());
        }
    }

    /**
     * Adds an Object Storage user.  Only works with administrative privileges.
     *
     * @param string $username
     * @param string $password
     * @param bool $isAdminUser Indicates if the new user is an admin or not
     *
     * @return bool
     *
     * @throws ObjectStorage_Exception_Http
     */
    public function addUser($username, $password, $isAdminUser = false)
    {
        $newUsername = trim($username);
        $password    = trim($password);
        if (empty($newUsername)) {
            throw new ObjectStorage_Exception('Username cannot be empty.');
        }

        if (empty($password)) {
            throw new ObjectStorage_Exception('Password cannot be empty.');
        }

        $client = $this->getHttpClient();
        $client->setMethod('PUT');

        list($account, $username) = explode(':', $this->username);

        $client->setUri($this->objectStorageHost . '/auth/v2/' . $account . '/' . $newUsername);
        $client->setHeaders('X-Auth-Admin-User', $this->username);
        $client->setHeaders('X-Auth-Admin-Key', $this->password);
        $client->setHeaders('X-Auth-User-Key', $password);

        if ($isAdminUser == true) {
            $client->setHeaders('X-Auth-User-Admin', 'true');
        }

        try {
            $response = $client->request();
        } catch (Exception $e) {
            throw ObjectStorage_Exception_Http::factory('Failed to create user.');
        }

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            return true;
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to create user.', $response->getStatusCode());
        }
    }

    /**
     * Deletes an Object Storage user
     *
     * @param string $username
     *
     * @return bool
     *
     * @throws ObjectStorage_Exception_Http
     */
    public function deleteUser($username)
    {
        $existingUsername = trim($username);
        if (empty($existingUsername)) {
            throw new ObjectStorage_Exception('Username cannot be empty.');
        }

        $client = $this->getHttpClient();
        $client->setMethod('DELETE');

        list($account, $username) = explode(':', $this->username);

        $client->setUri($this->objectStorageHost . '/auth/v2/' . $account . '/' . $existingUsername);
        $client->setHeaders('X-Auth-Admin-User', $this->username);
        $client->setHeaders('X-Auth-Admin-Key', $this->password);

        try {
            $response = $client->request();
        } catch (Exception $e) {
            throw ObjectStorage_Exception_Http::factory('Failed to delete user.');
        }

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            return true;
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to delete user.', $response->getStatusCode());
        }
    }

    /**
     * Returns ObjectStorage URL.
     * You will less likely use this method directly from ObjectStorage object. This method is used by ObjectStorage_Abstract object.
     *
     * @param string
     */
    public function getUrl(ObjectStorage_Abstract $objectStorageObject)
    {
        $authData = $this->getAuthenticationData();

        return $authData->objectStorageUrl . '/' . ltrim($objectStorageObject->getPath(), '/');
    }

    /**
     * Returns ObjectStorage CDN URLs.
     * You will less likely use this method directly from ObjectStorage object. This method is used by ObjectStorage_Abstract object.
     *
     * @param array
     */
    public function getCdnUrls(ObjectStorage_Abstract $objectStorageObject)
    {
        $authData = $this->getAuthenticationData();

        $cdnUrls = array();

        $headers = $objectStorageObject->getHeaders();

        if (count($headers) == 0 || $objectStorageObject->getContext() != ObjectStorage_Abstract::CONTEXT_CDN) {
            $authData = $this->getAuthenticationData();

            $client = $this->getHttpClient();
            $client->setHeaders('X-Auth-Token', $authData->authToken);
            $client->setHeaders('X-Context', 'cdn');
            $client->setMethod('HEAD');
            $client->setUri($authData->objectStorageUrl . '/' . rawurlencode(ltrim($objectStorageObject->getPath(), '/')));

            $response = $client->request();

            if ($this->isAcceptableResponse($response->getStatusCode())) {
                $objectStorageObject->setResponse($response);
            } else {
                throw ObjectStorage_Exception_Http::factory('Failed to retrieve "' . $objectStorageObject->getPath() . '".', $response->getStatusCode());
            }
        }

        $path = '/' . ltrim($objectStorageObject->getPath(), '/');
        foreach ($objectStorageObject->getResponse()->getHeaders() as $key => $val) {
            if (in_array(strtoupper($key), array('X-CDN-URL', 'X-CDN-STREAM-HTTP-URL', 'X-CDN-STREAM-FLASH-URL',
                                                    'X-CDN-CUSTOM-URL', 'X-CDN-CUSTOM-STREAM-HTTP-URL', 'X-CDN-CUSTOM-STREAM-FLASH-URL'))) {
                $cdnUrls[] = $val;
            }
        }

        return $cdnUrls;
    }

    /**
     * Creates a ObjectStorage container or an object
     *
     * @param ObjectStorage_Abstract $objectStorageObject
     *
     * @return ObjectStorage_Abstract
     */
    public function create(ObjectStorage_Abstract $objectStorageObject)
    {
        $authData = $this->getAuthenticationData();

        $client = $this->getHttpClient();
        $client->setUri($authData->objectStorageUrl . '/' . rawurlencode(ltrim($objectStorageObject->getPath(), '/')));
        $client->setHeaders('X-Auth-Token', $authData->authToken);
        $client->setMethod('PUT');

        $request = $objectStorageObject->getRequest();


        if ($objectStorageObject instanceof ObjectStorage_Object) {

            $localFile = $objectStorageObject->getLocalFile();

            if ($localFile != '') {

                if (! is_readable($localFile)) {
                    throw new ObjectStorage_Exception('Local file ' . $localFile . ' is not readable.');
                }

                $fileHander = fopen($localFile, 'r');
                if ($fileHander == false) {
                    throw new ObjectStorage_Exception('Failed to open local file ' . $localFile);
                }

                $client->setFileHandler($fileHander);

                // Override the content-length
                $request->setHeader('Content-Length', filesize($localFile));

            } else {
                $client->setBody($objectStorageObject->getRequest()->getBody());
            }

            if ($request->getHeader('Content-type') == '') {
                $request->setHeader('Content-type', ObjectStorage_Util::getMimeByName($objectStorageObject->getPath()));
            }
        }

        $headers = $request->getHeaders();

        if (count($headers) > 0) {
            foreach ($headers as $key => $value) {
                $client->setHeaders($key, $value);
            }
        }

        $response = $client->request();

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            $objectStorageObject->setResponse($response);
            return $objectStorageObject;
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to create ' . $objectStorageObject . '.', $response->getStatusCode());
        }
    }

    /**
     * Modifies a ObjectStorage container or an object
     *
     * @param ObjectStorage_Abstract $objectStorageObject
     *
     * @return ObjectStorage_Abstract
     */
    public function update(ObjectStorage_Abstract $objectStorageObject)
    {
        $authData = $this->getAuthenticationData();

        $client = $this->getHttpClient();
        $client->setUri($authData->objectStorageUrl . '/' . rawurlencode(ltrim($objectStorageObject->getPath(), '/')));
        $client->setHeaders('X-Auth-Token', $authData->authToken);
        $client->setMethod('POST');

        $request = $objectStorageObject->getRequest();
        $headers = $request->getHeaders();

        if (count($headers) > 0) {
            foreach ($headers as $key => $value) {
                $client->setHeaders($key, $value);
            }
        }

        if ($objectStorageObject instanceof ObjectStorage_Object) {
            $client->setBody($request->getBody());
        }

        $response = $client->request();

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            $objectStorageObject->setResponse($response);
            return $objectStorageObject;
        } else {
            throw ObjectStorage_Exception_Http::factory('Failed to save ' . $objectStorageObject . '. ' . $response->getBody(), $response->getStatusCode());
        }
    }

    /**
     * Deletes a ObjectStorage container or an object
     *
     * @param ObjectStorage_Abstract $objectStorageObject
     *
     * @return bool
     */
    public function delete(ObjectStorage_Abstract $objectStorageObject)
    {
        $authData = $this->getAuthenticationData();

        $uri = $authData->objectStorageUrl . '/' . rawurlencode($objectStorageObject->getPath());

        $queryParams = array();
        if (count($objectStorageObject->queryString) > 0) {
            foreach ($objectStorageObject->queryString as $key => $value) {
                $queryParams[] = $key . '=' . urlencode($value);
            }

            $uri .= '?' . implode('&', $queryParams);
        }

        $client = $this->getHttpClient();
        $client->setHeaders('X-Auth-Token', $authData->authToken);
        $client->setMethod('DELETE');
        $client->setUri($uri);

        $response = $client->request();

        if ($this->isAcceptableResponse($response->getStatusCode())) {
            $objectStorageObject->setResponse($response);
            return true;
        } else {
            throw ObjectStorage_Exception_Http::factory(null, $response->getStatusCode());
        }
    }

    protected function isAcceptableResponse($responseCode = 0)
    {
        return intval($responseCode / 200) == 1 ? true : false;
    }
}
