<?php

require 'tw-config.php'; // Define configuration constants
require 'core/tw-init.php';

$tw = new \Talkwork\Talkwork(
    new \Talkwork\InputFactory(PHP_SAPI),
    new \Talkwork\TwDB(DB_HOST, DB_USER, DB_PASS, DB_NAME)
);
echo $tw->out();
