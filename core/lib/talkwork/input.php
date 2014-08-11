<?php
/**
 * Interface for Input classes (cli and http)
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

abstract class Input
{
    private $args;
    private $data;
    private $method;
    
    public function __construct($args, $data, $method = 'cli')
    {
        $this->args = $args;
        $this->data = $data;
        $this->method = $method;
    }
    
    public function getArgs()
    {
        return $this->args;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function arg($i)
    {
        if (isset($this->args[$i])) {
            return $this->args[$i];
        } else {
            return false;
        }
    }
}
