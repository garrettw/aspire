<?php
/**
 * A (messy) test file that tests a password strength via pwstrength()
 */

// Display ALL errors
error_reporting(E_ALL);

// Load the required files
require '../config.php'; // Define configuration constants
require '../' . DIR_CORE . 'env-check.inc.php'; // Run environment checks
require '../' . DIR_CORE . 'functions.inc.php';


// Testing of default options
var_dump([
	"No password" => pwstrength(""),
	"Password with only lower case letters" => pwstrength("password"),
	"Password with mixed upper/lower case letters" => pwstrength("PASSword"),
	"Password with less than 8 chars" => pwstrength("pw"),
	"Password that has mixed chars & numbers" => pwstrength("Password12"),
	"Password that complies with the requirements" => pwstrength("Password12#"),
	"Short numerical password" => pwstrength("123456"),
	"Long numberical password" => pwstrength("1234567890123"),
	"Special character password" => pwstrength("#!@#!@#!@")
]);

echo "<hr />";


// Testing with custom options
$options = [
	"minChars"			=> 12,		// Minimum characters a password must have
	"numRequired"		=> true,	// At least one number is required
	"lcaseRequired"		=> false,	// At least one lower-case letter must be required
	"ucaseRequired"		=> false,	// At least one upper-case letter must be required
	"specialRequired"	=> false,	// There must be at least one special character (Non-alpha numeric)
];

var_dump([
	"No password" => pwstrength("", $options),
	"Password with only lower case letters" => pwstrength("password", $options),
	"Password with mixed upper/lower case letters" => pwstrength("PASSword", $options),
	"Password with less than 8 chars" => pwstrength("pw", $options),
	"Password that has mixed chars & numbers" => pwstrength("Password12", $options),
	"Password that complies with the requirements" => pwstrength("Password12#", $options),
	"Short numerical password" => pwstrength("123456", $options),
	"Long numberical password" => pwstrength("1234567890123", $options),
	"Special character password" => pwstrength("#!@#!@#!@", $options)
]);


echo "<hr />";

// Testing a password with little requirements
$options = [
	"minChars"			=> 6,		// Minimum characters a password must have
	"numRequired"		=> false,	// At least one number is required
	"lcaseRequired"		=> false,	// At least one lower-case letter must be required
	"ucaseRequired"		=> false,	// At least one upper-case letter must be required
	"specialRequired"	=> false,	// There must be at least one special character (Non-alpha numeric)
];

var_dump([
	"No password" => pwstrength("", $options),
	"Password with only lower case letters" => pwstrength("password", $options),
	"Password with mixed upper/lower case letters" => pwstrength("PASSword", $options),
	"Password with less than 8 chars" => pwstrength("pw", $options),
	"Password that has mixed chars & numbers" => pwstrength("Password12", $options),
	"Password that complies with the requirements" => pwstrength("Password12#", $options),
	"Short numerical password" => pwstrength("123456", $options),
	"Long numberical password" => pwstrength("1234567890123", $options),
	"Special character password" => pwstrength("#!@#!@#!@", $options)
]);
