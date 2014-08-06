<?php
/**
 * Command-line input class
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class CLInput extends Input
{
    public function __construct($args, $data)
    {
        if ($args == null) {
            $args = [];
        }
        
        stream_set_blocking($data, 0);
        $readin = [];
        while (($readin[] = fgets($data)) !== false) {}
        $data = $readin;
        
        parent::__construct($args, $data);
    }
}
