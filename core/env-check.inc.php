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

function error500($text) {
    header('HTTP/1.0 500 Internal Server Error');
    echo '<br><b>Fatal error</b>: ',$text;
    @trigger_error($text, E_USER_ERROR);
}

$req_version = '5.4.0';
if (version_compare($req_version, PHP_VERSION, '>')) {
    error500('Your server is running PHP ' . PHP_VERSION
          . ", but Talkwork requires at least version $req_version.");
    
}

if (preg_match('/[^A-Za-z0-9_]/',DB_TBLPREFIX)) {
    error500('DB_TBLPREFIX can only contain numbers, letters, and underscores.');
}

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
