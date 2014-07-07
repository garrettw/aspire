<?php
/**
 * Main file responsible for initializing framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

// REMOVE from here to END in production environment
if (ini_get('display_errors') == '0') {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL|E_STRICT);
// END

// Configuration constants
require 'config.php';
// Environment checks
require DIR_CORE . 'env-check.inc.php';

// Autoloader
require DIR_CORE . 'autoloader.php';
$loader = new Autoloader();
$loader->registerLibrary('core');

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
session_name(string_to_slug($twdb->configs['core']['site-n