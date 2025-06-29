<?php
/**
 * File:  /core/Hooks.class.php
 * Handles hooks that plugins can use to tie into the stream of execution outside of their normal operating time
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

class Hooks
{
    static $hooks = [];
  
    static function add ($hookname,$funcname) {
        if (isset(self::$hooks[$hookname])) {
            self::$hooks[$hookname][] = $funcname;
        } else {
            self::$hooks[$hookname] = [$funcname];
        }
    }
  
    static function remove ($hookname,$funcname) {
        if ($i = array_search($funcname,self::$hooks[$hookname])) {
            unset(self::$hooks[$hookname][$i]);
        }
    }
  
    static function run ($hookname) {
        if (isset(self::$hooks[$hookname])) {
            foreach (self::$hooks[$hookname] as $funcname) {
                $funcname();
            }
        }
    }

    static function destroy ($hookname) {
        unset(self::$hooks[$hookname]);
    }
}
