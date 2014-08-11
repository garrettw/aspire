<?php
/**
 * Class that handles errors and exceptions
 * 
 * @version    0.1
 * @author     David Tkachuk
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class ErrorHandler {

	public function __construct() {
		set_exception_handler([$this, "handleException"]);
		set_error_handler([$this, "handleError"]);
		register_shutdown_function([$this, "handleshutdownErrors"]);
	}
	
	public function __destruct() {
		restore_exception_handler();
		restore_error_handler();
	}

	/**
	 * Handles exceptions
	 */
	public function handleException($exception) {
		header('HTTP/1.1 500 Internal Server Error');
	
		// If debug mode is enabled, display the debug page
		if (defined('TW_DEBUGMODE') && TW_DEBUGMODE) {
			require TW_DIR . "/error/errorhandler.php";
			die();
		}
		
		// Otherwise display the public error message
		require TW_DIR . "/error/errorpublic.php";
		die();
	}
	
	/**
	 * Handles user errors
	 */
	public function handleError($errNo, $errStr, $errFile, $errLine) {
		throw new \Talkwork\Exception\Error($errStr, $errNo, $errFile, $errLine);
	}
	
	/**
	 * Handles all errors that are not user based
	 */
	public function handleShutdownErrors() {
		$lastError = error_get_last();
		if (!$lastError) return;
		
		$this->handleError($lastError['type'], $lastError['message'],
			$lastError['file'], $lastError['line']);
	}
	
	/**
	 *  Gets the error message name by the code
	 */
	public static function getErrorName($errorCode) {
		switch ($errorCode) {
			case E_ERROR:
				$errorName = "Fatal Error"; break;
			case E_WARNING:
				$errorName = "Warning"; break;
			case E_PARSE:
				$errorName = "Parse Error"; break;
			case E_NOTICE:
				$errorName = "Notice"; break;
			case E_CORE_ERROR:
				$errorName = "PHP Error"; break;
			case E_CORE_WARNING:
				$errorName = "PHP Warning"; break;
			case E_COMPILE_ERROR:
				$errorName = "Compile Error"; break;
			case E_COMPILE_WARNING:
				$errorName = "Compile Warning"; break;
			case E_USER_ERROR:
				$errorName = "User Error"; break;
			case E_USER_WARNING:
				$errorName = "User Warning"; break;
			case E_USER_NOTICE:
				$errorName = "User Notice"; break;
			case E_STRICT:
				$errorName = "String Error"; break;
			case E_RECOVERABLE_ERROR:
				$errorName = "Recoverable Error"; break;
			case E_DEPRECATED:
				$errorName = "DEPREACTED"; break;
			case E_USER_DEPRECATED:
				$errorName = "USER DEPREACTED"; break;
			default:
				$errorName = "Unknown Error"; break;
		}

		return $errorName;
	}
	
}
