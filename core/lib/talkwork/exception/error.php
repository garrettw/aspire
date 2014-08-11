<?php

namespace Talkwork\Exception;

class Error extends \Exception {

	public function __construct($errStr, $errNo, $errFile, $errLine) {
		$this->message = $errStr;
		$this->code = $errNo;
		$this->file = $errFile;
		$this->line = $errLine;
	}

}
