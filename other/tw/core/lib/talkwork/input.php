<?php
/**
 * Parent class for Input classes (cli and http)
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

abstract class Input
{
    private $route;
    private $args;
    private $dataStream;
    private $method;
    
    public function __construct($route, $args, $stream, $method)
    {
        $this->route = $route;
        $this->args = $args;
        $this->dataStream = $stream;
        $this->method = $method;
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
}
