<?php
/**
 * Route class
 * 
 * Mostly a struct except that properties are immutable.
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class Route
{
    private $module;
    private $res_type;
    private $res_id;
    
    public function __construct($mod, $restype, $resid)
    {
        $this->module   = $mod;
        $this->res_type = $restype;
        $this->res_id   = $resid;
    }
    
    public function __get($varname)
    {
        return $this->$varname;
    }
}
