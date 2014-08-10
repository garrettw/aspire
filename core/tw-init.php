<?php
/**
 * Main file responsible for prepping framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

if (defined('TW_DEBUGMODE') && TW_DEBUGMODE) {
    if (ini_get('display_errors') == '0') {
        ini_set('display_errors', '1');
    }
    error_reporting(E_ALL|E_STRICT);
}

function error500($text)
{
    header('HTTP/1.0 500 Internal Server Error');
    echo '<br><b>Fatal error</b>: ',$text;
    @trigger_error($text, E_USER_ERROR);
}

if (preg_match('/[^A-Za-z0-9_]/',DB_TBLPREFIX)) {
    error500('DB_TBLPREFIX can only contain numbers, letters, and underscores.');
}

$req_version = '5.4.0';
if (version_compare($req_version, PHP_VERSION, '>')) {
    error500('Your server is running PHP ' . PHP_VERSION
          . ", but Talkwork requires at least version $req_version.");
}

define('TW_DIR', __DIR__);
require TW_DIR . '/autoloader.php';
$loader = new Autoloader();
$loader->registerLibrary(TW_DIR); // where autoload.json lives
