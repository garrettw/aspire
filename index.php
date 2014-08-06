<?php

// REMOVE from here to 'END' in production environment
if (ini_get('display_errors') == '0') {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL|E_STRICT);
// END


require 'tw-config.php'; // Define configuration constants
require 'core/tw-init.php';

$tw = new \Talkwork\Talkwork(
    new \Talkwork\InputFactory(PHP_SAPI),
    new \Talkwork\TwDB(DB_HOST, DB_USER, DB_PASS, DB_NAME)
);
echo $tw->out();
