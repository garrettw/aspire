<?php
/**
 * File:  /index.php
 * Root file responsible for initializing system & handling requests
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

if (ini_get('display_errors') == '0') {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL|E_STRICT);
// end devel mode settings

$php_min_version = '5.3.2';
if (version_compare($php_min_version, PHP_VERSION,'>')) {
    header('HTTP/1.0 500 Internal Server Error');
    $text = 'Your server is running PHP ' . PHP_VERSION
          . ', but Talkwork requires at least version ' . $php_min_version . '.';
    echo '<br><b>Fatal error</b>: ',$text;
    @trigger_error($text, E_FATAL);
}


require 'config.php';
include DIR_CORE . 'Error.class.php';
require DIR_CORE . 'env-check.inc.php';
require DIR_CORE . 'MySQLDB.class.php';
require DIR_CORE . 'TwDB.class.php';
    $twdb = new TwDB(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    define('CUR_THEME', (isset($_GET['theme'])) ? $_GET['theme']
                                     : $twdb->configs['core']['default-theme']);
require DIR_CORE . 'functions.inc.php';
require DIR_CORE . 'parseurl.inc.php';
require DIR_CORE . 'User.class.php';

if (function_exists('mb_internal_encoding')
    && !@mb_internal_encoding($twdb->configs['core']['charset'])
) {
    mb_internal_encoding('UTF-8');
}
session_name(string_to_slug($twdb->configs['core']['site-name']));
session_start();

include DIR_CORE . 'Hooks.class.php';

if (count($twdb->activeplugins) != 0) {
    foreach ($twdb->activeplugins as $plugin) {
        include_plugin_file($plugin . '/main.php');
    }
}

//Hooks::run('before-controller');

//require DIR_CORE . 'Module.iface.php';

load_controller(CUR_MC);

//Hooks::run('after-controller');
//Hooks::run('before-header');

//Hooks::run('after-header');
//Hooks::run('before-view');

//Hooks::run('after-view');
//Hooks::run('before-footer');

//Hooks::run('after-footer');
