<?php
/**
 * File:  /core/env-check.inc.php
 * Makes sure our server environment matches all requirements
 * Some parts taken from WordPress/wp-settings.php
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

// Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
if (isset($_SERVER['SCRIPT_FILENAME'])
    && (strpos($_SERVER['SCRIPT_FILENAME'],'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME'])-7)
) {
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
}

// Fix for Dreamhost and other PHP as CGI hosts
if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
	unset($_SERVER['PATH_INFO']);
}

// Fix empty PHP_SELF
$PHP_SELF = $_SERVER['PHP_SELF'];
if (empty($PHP_SELF)) {
	$_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace('/(\?.*)?$/','',$_SERVER['REQUEST_URI']);
}

if (!extension_loaded('mysql')) {
	Error::send(500,E_FATAL,'Your PHP installation appears to be missing the MySQL extension which is required by Talkwork.');
}

if (preg_match('/[^A-Za-z0-9_]/',DB_TBLPREFIX)) {
    Error::send(500,E_FATAL,'DB_TBLPREFIX can only contain numbers, letters, and underscores.');
}
