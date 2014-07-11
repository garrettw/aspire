<?php
/**
 * Main file responsible for initializing framework
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

define('TW_DIR', __DIR__);

require TW_DIR . '/env-check.inc.php'; // Run environment checks
require TW_DIR . '/autoloader.php';

$loader = new Autoloader();
$loader->registerLibrary(TW_DIR); // where autoload.json lives

function something (MySQLDB $db) {
    define('CUR_THEME', (isset($_GET['theme'])) ? $_GET['theme']
            : $db->configs['core']['default-theme']);
    require TW_DIR . '/functions.inc.php';
    require TW_DIR . '/router.inc.php';
    
    if (function_exists('mb_internal_encoding')
        && !@mb_internal_encoding($db->configs['core']['charset'])
    ) {
        mb_internal_encoding('UTF-8');
    }
    session_name(string_to_slug($db->configs['core']['site-name']));
    session_start();
    
    if (count($db->activeplugins) != 0) {
        foreach ($db->activeplugins as $plugin) {
            include_plugin_file($plugin . '/main.php');
        }
    }
    load_controller(CUR_MC);
}

//Hooks::run('before-controller');
//Hooks::run('after-controller');
//Hooks::run('before-view');
//Hooks::run('after-view');
