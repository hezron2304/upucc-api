<?php
namespace App\Core;

class AppException extends \Exception
{
    protected $httpCode;

    public function __construct($message, $code = 500)
    {
        parent::__construct($message);
        $this->httpCode = $code;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }
}
?>