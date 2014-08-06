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
    
    public function __construct($args, $data)
    {
        $this->args = $args;
        $this->data = $data;
    }
    
    public function getArgs()
    {
        return $this->args;
    }
    
    public function getData()
    {
        return $this->data;
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
