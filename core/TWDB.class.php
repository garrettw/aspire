<?php
/**
 * File:  /core/TWDB.class.php
 * Database class extended with TW-specific methods
 * 
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

class TWDB extends MySQLDB
{
    public $configs_core;
    public $activeplugins;
    
    function __construct ($host,$user,$pass,$dbname) {
        parent::__construct($host,$user,$pass,$dbname);
        $this->configs_core = $this->get_configs('core');
        $this->activeplugins = $this->get_plugins();
    }
    
    function get_configs ($module) {
        $ta = $this->get('SELECT `key`,`value` FROM `'.DB_TBLPREFIX
              ."config` WHERE `module` = '$module'"
        );
        $tr = array();
        foreach ($ta as &$e) {
            $tr[$e['key']] = $e['value'];
        }
        return $tr;
    }

    function set_configs ($module,$configs) {
        $rv = TRUE;
        foreach ($configs as $key => $value) {
            $rv = $rv & $this->set_config_value($module,$key,$value);
        }
        return $rv;
    }
  
    function get_config_value ($module,$key) {
        return $this->get_one_field('SELECT `value` FROM `'.DB_TBLPREFIX
               ."config` WHERE `module` = '$module' AND `key` = '$key'"
        );
    }

    function set_config_value ($module,$key,$value) {
        if ($this->count('SELECT * FROM `'.DB_TBLPREFIX
            ."config` WHERE `module` = '$module' AND `key` = '$key'") == 0
        ) {
            return $this->q('INSERT INTO `'.DB_TBLPREFIX
                   .'config` (`module`,`key`,`value`) '
                   ."VALUES ('$module','$key','$value')");
        } else {
            return $this->q('UPDATE `'.DB_TBLPREFIX
                   ."config` SET `value` = '$value' "
                   ."WHERE `module` = '$module' AND `key` = '$key'");
        }
    }
  
    function get_plugins ($formodule='*',$active=1) {
        $q = 'SELECT `name` FROM `'.DB_TBLPREFIX.'plugins`';
        $donewhere = FALSE;
        if ($active != 2) {
            $q .= " WHERE `active` = $active";
            $donewhere = TRUE;
        }
        if ($formodule != '*') {
            $q .= ($donewhere ? ' AND ' : ' WHERE ')
                  ."`for-modules` LIKE '$formodule'";
        }
    
        $ta = $this->get($q);
        $tr = array();
        foreach ($ta as &$e) {
            $tr[] = $e['name'];
        }
        return $tr;
    }
}
