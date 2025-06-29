<?php
/**
 * File:  /core/User.class.php
 * Manages users and provides functions for working with them
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

namespace Talkwork;

class User {
    public $info;
    public $perms;
    private static $checked = false;

    private function __construct($qrow)
    {
        $this->info = $qrow;
        $this->perms = $GLOBALS['twdb']->get_one_row('SELECT * FROM `'
                      .DB_TBLPREFIX.'roles` WHERE `id` = '.$this->info['role']);
    }

    private static function get1q($q)
    {
        $qrow = $GLOBALS['db']->get_one_row('SELECT * FROM `'.DB_TBLPREFIX
                                            .'users` WHERE '.$q);
        if ($qrow) {
            return new self($qrow);
        }
        return false;
    }
  
    static function get_by_id($id)
    {
        return self::get1q("`id` = $id");
    }
  
    static function get_by_username($u)
    {
        return self::get1q("`username` = '$u'");
    }
  
    function save_info($oldpw)
    {
        if (self::login_valid($this->info['username'],$oldpw)) {
            $keyvals = '';
            foreach ($info as $key => $val) {
                if ($key == 'username') {
                    continue;
                }
                if (is_string($val)) {
                    $val = "'$val'";
                }
                $keyvals .= "`$key` = $val, ";
            }
            $keyvals = substr($keyvals,0,-2);
            return $GLOBALS['db']->q('UPDATE `'.DB_TBLPREFIX.'users` SET '
                .$keyvals.' WHERE `username` = \''.$this->info['username'].'\'');
        }
        return false;
    }

    static function exists($u)
    {
        global $twdb;
        $q = 'SELECT * FROM `'.DB_TBLPREFIX."users` WHERE ";
        if ($twdb->configs_core['login-with'] == 'both') {
            $q .= "(`username` = '$u' OR `email` = '$u')";
        } else {
            $q .= "`username` = '$u'";
        }
        return ($twdb->count($q) == 1);
    }
  
    static function login_valid($u,$pw)
    {
        global $twdb;
        $q = 'SELECT * FROM `'.DB_TBLPREFIX."users` WHERE ";
        if ($twdb->configs_core['login-with'] == 'both') {
            $q .= "(`username` = '$u' OR `email` = '$u')";
        } else {
            $q .= "`username` = '$u'";
        }
        $hash = pwhash($pw);
        $q .= " AND `pwhash` = '$hash'";
        return ($twdb->count($q) == 1);
    }
  
    static function logged_in(&$err)
    {
        global $twdb;
        $err = '';
        if (self::$checked) {
            return true;
        } else if (isset($_SESSION['pwhash'])) {
            if ($twdb->configs_core['login-timeout'] > 0
                && time() - $_SESSION['lastaccess']
                    >= $twdb->configs_core['login-timeout']
            ) {
                unset($_SESSION['pwhash']);
                $err = 'timeout';
                return false;
            }
            if (($user = self::get_by_id($_SESSION['id']))
                && $_SESSION['pwhash'] == $user->info['pwhash']
            ) {
                self::$checked = true;
                return true;
            } else {
                unset($_SESSION['pwhash']);
                $err = 'invalid';
                return false;
            }
        }
        return false;
    }
  
    static function create($info)
    {
        global $twdb;
        if (is_array($info)
            && (isset($info['username'])
                || ($twdb->configs_core['login-with'] == 'email' 
                    && isset($info['email']))) 
            && isset($info['pwhash'])
        ) {
            if (!isset($info['role'])) {
                $info['role'] = $twdb->configs_core['default-role'];
            }
            $keys = implode('`,`',array_keys($info));
            $vals = implode('\',\'',array_values($info));
            return $GLOBALS['db']->q('INSERT INTO `'.DB_TBLPREFIX
                                .'users` (`'.$keys.'`) VALUES (\''.$vals.'\')');
        }
        return false;
    }
  
    static function delete ($u,$pw = null) {
        if (self::is_valid($u,$pw)) {
            return $GLOBALS['db']->q('DELETE FROM `'.DB_TBLPREFIX
                                     .'users` WHERE `username` = \''.$u.'\'');
        }
        return false;
    }
}
