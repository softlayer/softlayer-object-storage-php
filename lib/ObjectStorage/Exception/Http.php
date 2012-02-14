<?php

class ObjectStorage_Exception_Http extends ObjectStorage_Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);

        if (is_numeric($code) && $code > 0) {
            $this->throwException($code, $message, $this->file, $this->line);
        } else {
            throw new ObjectStorage_Exception($message);
        }
    }

    protected function throwException($responseCode, $message, $class, $line)
    {
        $errorMessage = null;
        switch ($responseCode) {
            case 400:
                $errorMessage = 'Bad Request';
                break;
            case 401:
                $errorMessage = 'Unauthorized';
                break;
            case 403:
                $errorMessage = 'Forbidden';
                break;
            case 404:
                $errorMessage = 'Not Found';
                break;
            case 405:
                $errorMessage = 'Method Not Allowed';
                break;
            case 406:
                $errorMessage = 'Not Acceptable';
                break;
            case 407:
                $errorMessage = 'Proxy Authentication Required';
                break;
            case 408:
                $errorMessage = 'Request Timeout';
                break;
            case 409:
                $errorMessage = 'Conflict';
                break;
            case 500:
                $errorMessage = 'Internal Server Error';
                break;
            case 501:
                $errorMessage = 'Not Implemented';
                break;
            case 502:
                $errorMessage = 'Bad Gateway';
                break;
        }

        if ($errorMessage != null) {
            $exception = __CLASS__ . '_' . str_replace(' ', '', $errorMessage);
        } else {
            $exception = __CLASS__;
            $errorMessage = 'Unable to process your request';
        }

        if ($message == '') {
            $message = $errorMessage;
        }

        ObjectStorage_Util::__autoload_objectStorage_client($exception);
        if (! class_exists($exception)) {
            $exception = 'ObjectStorage_Exception';
        }

        $newException = new $exception($message);
        $newException->setFile($class);
        $newException->setLine($line);

        throw $newException;
    }
}