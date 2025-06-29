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

define('TW_DEBUGMODE', true);

define('DEF_MODULE', 'static');
define('DEF_RESTYPE', 'page');
define('DEF_RESID', 'home');

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'talkwork');
define('DB_TBLPREFIX', '');

// directory of installation, as accessed from the Internet - both slashes required
define('WS_ROOT', '/');
define('WS_ROOT_LENGTH', strlen(WS_ROOT));

