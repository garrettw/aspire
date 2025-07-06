<?php
/**
 * HTTP input class
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class HTTPInput extends Input
{
    public function __construct($args)
    {
        // Strip the query string out of the raw request
        $q = $_SERVER['REQUEST_URI'];
        $qpos = strpos($q, '?');
        if ($qpos !== false) {
            $q = substr($q, 0, $qpos);
        }
        
        parent::__construct('', [0 => $q] + $args, 'php://input', $_SERVER['REQUEST_METHOD']);
        
        // Fix empty PHP_SELF
    	$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?: $q;
    	
    	// Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something
        // ending in php.cgi for all requests
        if (isset($_SERVER['SCRIPT_FILENAME'])
            && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi')
                == strlen($_SERVER['SCRIPT_FILENAME'])-7
            )
        ) {
        	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
        }

        // Fix for Dreamhost and other PHP as CGI hosts
        if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
        	unset($_SERVER['PATH_INFO']);
        }
    }
}
