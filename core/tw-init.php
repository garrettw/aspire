<?php
/**
 * Main file responsible for prepping framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

define('TW_DIR', __DIR__);
$req_version = '5.4.0';

function error500($text)
{
    header('HTTP/1.0 500 Internal Server Error');
    echo '<br><b>Fatal error</b>: ',$text;
    @trigger_error($text, E_USER_ERROR);
}

if (version_compare($req_version, PHP_VERSION, '>')) {
    error500('Your server is running PHP ' . PHP_VERSION
          . ", but Talkwork requires at least version $req_version.");
}

if (preg_match('/[^A-Za-z0-9_]/',DB_TBLPREFIX)) {
    error500('DB_TBLPREFIX can only contain numbers, letters, and underscores.');
}

require TW_DIR . '/autoloader.php';

$loader = new Autoloader();
$loader->registerLibrary(TW_DIR); // where autoload.json lives
