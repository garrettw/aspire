<?php
/**
 * File:  /config.php
 * Defines database connection parameters
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'talkwork');
define('DB_TBLPREFIX', '');

// directory of installation, as accessed from the Internet - both slashes required
define('WS_ROOT', '/talkwork/master/');
define('WS_ROOT_LENGTH', strlen(WS_ROOT));

// directory of installation as accessed locally from the server itself
define('FS_ROOT', dirname(__FILE__) . '/');

define('DIR_CORE', 'core/'); // location of core application files
define('DIR_MODULES', 'core/'); // where module directories are located
define('DIR_PLUGINS', 'plugins/'); // where plugin directories are located
define('DIR_THEMES', 'themes/'); // where theme directories are located
