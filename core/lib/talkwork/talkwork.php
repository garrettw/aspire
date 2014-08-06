<?php
/**
 * Base class for instantiating the framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class Talkwork
{
    private $in; // holds an object of class Input
    
    public function __construct(InputFactory $inf, MySQLDB $db)
    {
        $this->in = $inf->build();
            
        require TW_DIR . 'router.inc.php';
        
        if (function_exists('mb_internal_encoding')
            && !@mb_internal_encoding($db->configs['core']['charset'])
        ) {
            mb_internal_encoding('UTF-8');
        }
        session_name($this->string_to_slug($db->configs['core']['site-name']));
        session_start();
        
        if (count($db->activeplugins) != 0) {
            foreach ($db->activeplugins as $plugin) {
                $f = DIR_PLUGINS . $plugin . '/main.php';
                if (file_exists($f)) {
                    include $f;
                } else {
                    Error::send(200, E_NONFATAL, "Plugin file '$f' not found.");
                }
            }
        }
        
        $cpath = DIR_MODULES . CUR_MC . '.ctrl.php';
        if (file_exists($cpath)) {
            include $cpath;
        } else {
            Error::send(404, E_FATAL, "Controller '". CUR_MC ."' not found.");
        }
    }
    
    public function string_to_slug($s)
    {
        $map = [
            '/[!"#\'\(\)\*,\-\.:;\?`‘’“”–— ´]/' => '',
            '/[ \/\\…·]/'                       => '-',
            '/(\d+)%/'                          => '$1-percent',
            '/&/'                               => 'and',
            '/(==|=)/'                          => 'equals',
        ];
        return strtolower(preg_replace(array_keys($map), array_values($map), $s));
    }
}
