<?php
/**
 * Main file responsible for prepping framework
 *
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

if (defined('TW_DEBUGMODE') && TW_DEBUGMODE):
    if (ini_get('display_errors') == '0'):
        ini_set('display_errors', '1');
    endif;
    error_reporting(E_ALL|E_STRICT);
endif;

$req_version = '5.4.0';
if (version_compare($req_version, PHP_VERSION, '>')):
    $text = 'Your server is running PHP ' . PHP_VERSION
          . ", but Talkwork requires at least version $req_version.";
    header('HTTP/1.0 500 Internal Server Error');
    echo '<br><b>Fatal error</b>: ',$text;
    @trigger_error($text, E_USER_ERROR);
endif;

require __DIR__ . '/autoloader.php';
$loader = new Autoloader();
$loader->registerLibrary(__DIR__); // where autoload.json lives

$dic = new \Dice\Dice;

// shared Input should be created as the proper subclass
$rule = new \Dice\Rule;
if (PHP_SAPI == 'cli'):
    $rule->instanceOf = 'Talkwork\\CLInput';
    $rule->constructParams = [$argv];
else:
    $rule->instanceOf = 'Talkwork\\HTTPInput';
    $rule->constructParams = [$_GET];
endif;
$rule->shared = true;
$dic->addRule('Talkwork\\Input', $rule);

// Talkwork\App should make its Route with specific params
$rule = new \Dice\Rule;
$rule->substitutions['Talkwork\\Route'] = function() {
    return new \Talkwork\Route(DEF_MODULE, DEF_RESTYPE, DEF_RESID);
};
$dic->addRule('Talkwork\\App', $rule);

// shared DB should be created with specific params
$rule = new \Dice\Rule;
$rule->constructParams = [DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TBLPREFIX];
$rule->shared = true;
$dic->addRule('Talkwork\\DB', $rule);