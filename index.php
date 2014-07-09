<?php
/**
 * Main file responsible for initializing framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

// REMOVE from here to 'END' in production environment
if (ini_get('display_errors') == '0') {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL|E_STRICT);
// END

require 'config.php'; // Define configuration constants
require DIR_CORE . 'env-check.inc.php'; // Run environment checks
require DIR_CORE . 'autoloader.php';

$loader = new Autoloader();
$loader->registerLibrary('core'); // talkwork lives in 'core' dir

// create DB connection instance
$twdb = new TwDB(DB_HOST,DB_USER,DB_PASS,DB_NAME);

define('CUR_THEME', (isset($_GET['theme'])) ? $_GET['theme']
        : $twdb->configs['core']['default-theme']);
require DIR_CORE . 'functions.inc.php';
require DIR_CORE . 'router.inc.php';

if (function_exists('mb_internal_encoding')
    && !@mb_internal_encoding($twdb->configs['core']['charset'])
) {
    mb_internal_encoding('UTF-8');
}
session_name(string_to_slug($twdb->configs['core']['site-name']));
session_start();

if (count($twdb->activeplugins) != 0) {
    foreach ($twdb->activeplugins as $plugin) {
        include_plugin_file($plugin . '/main.php');
    }
}

//Hooks::run('before-controller');

load_controller(CUR_MC);

//Hooks::run('after-controller');
//Hooks::run('before-view');

//Hooks::run('after-view');
