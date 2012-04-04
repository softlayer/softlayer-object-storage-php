<?php
/**
 * ObjectStorage base exception
 *
 * @see ObjectStorage_Abstract
 *
 * @package ObjectStorage-Client
 * @copyright  Copyright (c) 2012 SoftLayer Technologies Inc. (http://www.softlayer.com)
 */
class ObjectStorage_Exception extends Exception
{
    /**
     * Sets the file name of which an exception is thrown
     *
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Sets the line number of which an exception is thrown
     *
     * @param int $line
     */
    public function setLine($line)
    {
        $this->line = $line;
    }
}