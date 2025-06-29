<?php
/**
 * File:  /core/Error.class.php
 * Handles fancy displaying of Talkwork error messages
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

namespace Talkwork;

define('E_FATAL',      E_USER_ERROR);
define('E_NONFATAL',   E_USER_WARNING);
define('E_SUGGESTION', E_USER_NOTICE);

define('HTTP_OK',       200);
define('HTTP_BADREQ',   400);
define('HTTP_UNAUTH',   401);
define('HTTP_FORBID',   403);
define('HTTP_NOTFOUND', 404);
define('HTTP_INSVERR',  500);

class Error
{
    private static $codes = [HTTP_BADREQ   => 'Bad Request',
                             HTTP_UNAUTH   => 'Unauthorized',
                             HTTP_FORBID   => 'Forbidden',
                             HTTP_NOTFOUND => 'Not Found',
                             HTTP_INSVERR  => 'Internal Server Error',
    ];
    private static $elevels = [E_FATAL      => 'Fatal error',
                               E_NONFATAL   => 'Error',
                               E_SUGGESTION => 'FYI',
    ];
    private static $sent = [];
    private static $stack = [];
    private static $stacksize = 0;

    static function send ($httpcode,$ecode,$text,$once=FALSE) {
        if (!($once && in_array($text,self::$sent))) {
            if ($httpcode != HTTP_OK && !headers_sent()) {
                header($_SERVER['SERVER_PROTOCOL'] . " $httpcode "
                       . self::$codes[$httpcode]);
            }
            echo '<br><b>' . self::$elevels[$ecode] . "</b>: $text";
            @trigger_error($text,$ecode);
            if ($once) {
                self::$sent[] = $text;
            }
        }
    }

    static function push ($httpcode,$severity,$text,$once=FALSE) {
        self::$stack[] = [$httpcode,$severity,$text,$once];
        self::$stacksize++;
    }

    static function pop () {
        if (self::$stacksize > 1) {
            self::$stacksize--;
            self::send(self::$stack[self::$stacksize][0],
                       self::$stack[self::$stacksize][1],
                       self::$stack[self::$stacksize][2],
                       self::$stack[self::$stacksize][3]);
            unset(self::$stack[self::$stacksize]);
            return true;
        } else {
            return false;
        }
    }

    static function flush () {
        while(self::pop());
    }
}
