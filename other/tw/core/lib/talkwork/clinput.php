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
    public function __construct($args)
    {
        if ($args == null) {
            $args = [];
        } else {
            // todo: implement parsing of command line switches & args.
            // something like getopt() or better
        }
        
        /*
        stream_set_blocking($data, 0);
        $readin = [];
        while (($readin[] = fgets($data)) !== false) {}
        $data = $readin;
        */
        
        parent::__construct('', $args, 'php://input', 'cli');
    }
}
