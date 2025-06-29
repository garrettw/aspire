<?php

require 'tw-config.php'; // Define configuration constants
require 'core/tw-init.php'; // Set up environment

$tw = $dic->create('Talkwork\\App');
$tw->run();
echo $tw->out();
