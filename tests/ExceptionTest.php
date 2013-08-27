<?php
require_once(dirname(__FILE__) . '/BaseTest.php');

class ExceptionTest extends BaseTest
{
    public function testHttpExceptions()
    {
        $httpCodes = array(
            400 => 'BadRequest',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'NotFound',
            405 => 'MethodNotAllowed',
            406 => 'NotAcceptable',
            407 => 'ProxyAuthenticationRequired',
            408 => 'RequestTimeout',
            409 => 'Conflict',
            500 => 'InternalServerError',
            501 => 'NotImplemented',
            502 => 'BadGateway'
        );

        foreach ($httpCodes as $httpCode => $classSuffix) {

            $e = ObjectStorage_Exception_Http::factory('', $httpCode);

            $this->assertInstanceOf('ObjectStorage_Exception_Http_' . $classSuffix, $e);
            $this->assertEquals($httpCode, $e->getCode());
        }
    }

    public function testHttpExceptionInvalidCode()
    {
        $invalidCode = 1000000000;
        $e = ObjectStorage_Exception_Http::factory('', $invalidCode);

        // Expecting the base HTTP exception
        $this->assertInstanceOf('ObjectStorage_Exception_Http', $e);
        $this->assertEquals($invalidCode, $e->getCode());
    }
}