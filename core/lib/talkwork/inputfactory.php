<?php
/**
 * Factory for Input objects
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class InputFactory
{
    private $type;
    
    public function __construct($type = PHP_SAPI) {
        $this->type = $type;
    }
    
    public function build()
    {
        if ($this->type == 'cli') {
            return new CLInput($argv, STDIN);
        } else {
            return new HTTPInput($_GET, $_POST);
        }
    }
    
    public function getType()
    {
        return $this->type;
    }
}
