<?php
/**
 * File:  /core/DB.class.php
 * Database class extended with Tw-specific methods
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

namespace Talkwork;

class DB extends MySQLDB
{
    public $configs;
    public $activeplugins;
    private $tblprefix;

    function __construct($host,$user,$pass,$dbname,$tblprefix)
    {
        parent::__construct($host,$user,$pass,$dbname);

        if (preg_match('/[^A-Za-z0-9_]/',$tblprefix)) {
            $text = '$tblprefix can only contain numbers, letters, and underscores.';
            header('HTTP/1.0 500 Internal Server Error');
            echo '<br><b>Fatal error</b>: ',$text;
            @trigger_error($text, E_USER_ERROR);
        } else {
            $this->tblprefix = $tblprefix;
        }
        $this->get_configs();
        $this->activeplugins = $this->get_plugins();
    }

    function get_configs()
    {
        // disabling some things until i fix the db structure.
        // $ta = $this->read('SELECT `module`,`key`,`value`,`serialized` FROM `'
        //         .DB_TBLPREFIX.'config`');
        $ta = $this->read('SELECT `module`,`key`,`value` FROM `'.DB_TBLPREFIX
            .'config`'
        );
        $tr = [];
        foreach ($ta as &$e) {
            // if ($e['serialized']) {
            //     $tr[$e['module']][$e['key']]
            //         = unserialize(base64_decode($e['value']));
            // } else {
                $tr[$e['module']][$e['key']] = $e['value'];
            // }
        }
        $this->configs = $tr;
    }

    function set_configs($module,$configs)
    {
        $rv = true;
        foreach ($configs as $key => $value) {
            if ($key == 'shortcuts') {
                $value = base64_encode(serialize($value));
            }
            $rv = $rv & $this->set_config_value($module,$key,$value);
        }
        return $rv;
    }

    function get_config_value($module,$key)
    {
        $value = $this->read_one_field('SELECT `value` FROM `'.DB_TBLPREFIX
                 ."config` WHERE `module` = '$module' AND `key` = '$key'");
        if ($key == 'shortcuts') {
            return unserialize(base64_decode($value));
        } else {
            return $value;
        }
    }

    function set_config_value($module,$key,$value)
    {
        if ($key == 'shortcuts') {
            $value = base64_encode(serialize($value));
        }
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

    function get_plugins($formodule='*',$active=1)
    {
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

        $ta = $this->read($q);
        $tr = [];
        foreach ($ta as &$e) {
            $tr[] = $e['name'];
        }
        return $tr;
    }
}
