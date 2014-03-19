<?php

/**
 * Registering auto loader
 */
spl_autoload_register('ObjectStorage_Util::__autoload_objectStorage_client');

/**
 * ObjectStorage client utility class
 * It's used for autoloading files for now.
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Util
{
    /**
     * Attempts to load a file
     *
     * @param string $className
     */
    public static function __autoload_objectStorage_client($className)
    {
        $objectStorageDirectory = dirname(dirname(__FILE__));
        set_include_path(get_include_path() . PATH_SEPARATOR . $objectStorageDirectory);

        $directoryChunks = explode('_', $className);

        $path = implode(DIRECTORY_SEPARATOR, $directoryChunks) . '.php';

        if (file_exists($objectStorageDirectory . DIRECTORY_SEPARATOR . $path)) {
            require_once($path);
        }
    }

    public static function getMimeByName($fileName = '')
    {
        // mime_content_type is depricated and finfo_file is requires PHP >= 5.3.0
        $mimeTypes = array(
            'ai' => 'application/postscript',
            'atom' => 'application/atom+xml',
            'avi' => 'video/x-msvideo',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'cab' => 'application/vnd.ms-cab-compressed',
            'css' => 'text/css',
            'dmg' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dtd' => 'application/xml-dtd',
            'eps' => 'application/postscript',
            'exe' => 'application/x-msdownload',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ico' => 'image/vnd.microsoft.icon',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'msi' => 'application/x-msdownload',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'pdf' => 'application/pdf',
            'php' => 'text/html',
            'png' => 'image/png',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'psd' => 'image/vnd.adobe.photoshop',
            'qt' => 'video/quicktime',
            'rar' => 'application/x-rar-compressed',
            'rtf' => 'application/rtf',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'txt' => 'text/plain',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'zip' => 'application/zip'
        );

        $chunks = explode('.', $fileName);

        $extension = '';
        if (count($chunks) > 1) {
            $extension = array_pop($chunks);
        }

        if ($extension == '') {
            return 'application/directory';
        } else if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        } else if (function_exists('mime_content_type')) {
            return mime_content_type($fileName);
        } else {
            return 'application/octet-stream';
        }
    }
}