<?php
namespace Spider\Crawler;
/**
* 未实现的异常
*/
class NotImplementException extends \Exception {
    function __construct($message='', $code=0, \Exception $previous=null) {
        parent::__construct($message, $code, $previous);
    }
}