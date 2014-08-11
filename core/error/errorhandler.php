<?php

if ($exception instanceof \Talkwork\Exception\Error) {
	$message = "<strong>" . \Talkwork\ErrorHandler::getErrorName(
				$exception->getCode()) . "</strong> ";
} else {
	$message = "[" . $exception->getCode() . "] ";
}

$message .= $exception->getMessage();

?>
<!DOCTYPE>
<html>
<head>
	<title>Talkwork Error Debugger</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<style type="text/css">
		html, body {
			margin: 0;
			padding: 0;
			font-size: 15px;
			font-family: Verdana, Arial, Sans-Serif;
		}
		div.header {
			background: #1389B7;
			padding: 18px 32px;
			font-size: 17px;
			color: #FFFFFF;
		}
		div.footer {
			background: #797979;
			padding: 18px 32px;
			font-size: 14px;
			color: #FFFFFF;
		}
		a {
			text-decoration: none;
		}
		a:hover {
			text-decoration: underline;
		}
		div.header a, div.footer a {
			color: #F5F5F5;
		}
		.left {
			float: left;
		}
		.right {
			float: right;
		}
		.clear {
			clear: both;
		}
		.trace {
			background: #E8E8E8;
			padding: 24px 38px;
			border-bottom: 1px solid #FFFFFF;
		}
		table.trace-args {
			background: #FFFFFF;
			width: 100%;
			margin-top: 24px;
		}
		table.trace-args tr:nth-child(even) td {
			background: #F2F2F2;
		}
		table.trace-args th {
			background: #1488B5;
			color: #FFFFFF;
			padding: 5px 9px;
		}
		table.trace-args td {
			padding: 5px 9px;
		}
		th.arg-num {
			width: 30px;
		}
		th.arg-type {
			width: 100px;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<?php echo $message; ?>
		</div>
		
		<div class="content">
			<div class="source trace">
				<?php echo $exception->getFile() . " line " . $exception->getLine(); ?>
			</div>
<?php
	$stackTrace = $exception->getTrace();
	
	// If there are are trace, loop through them
	if (isset($stackTrace) && !empty($stackTrace) && is_array($stackTrace)) {
		echo "<div class=\"stacktrace\">";
	
		foreach ($stackTrace as $number => $trace) {
			$traceFunction = (isset($trace['class']) && !empty($trace['class']) ? $trace['class'] . $trace['type'] : "") 
								. $trace['function'];
		
			echo "<div class=\"trace\"><em><strong>" . $traceFunction . "</strong></em>" .
				(isset($trace['file']) && !empty($trace['file']) ? " at <em>" . $trace['file'] .
					" line " . $trace['line'] . "</em>" : "");
			
			// If the trace has any arguments, loop through them
			if (isset($trace['args']) && !empty($trace['args']) && is_array($trace['args'])) {
				echo "<table class=\"trace-args\">
					<tr>
						<th class=\"arg-num\">#</th>
						<th class=\"arg-type\">Type</th>
						<th class=\"arg-value\">Value</th>
					</tr>";

				foreach ($trace['args'] as $key => $value) {
					$type = gettype($value);

					if ($type == "array" || $type == "object") {
						$value = "<pre>" . print_r($value, true) . "</pre>";
					}
				
					echo "<tr>
						<td>" . $key . "</td>
						<td>" . $type . "</td>
						<td>" . $value . "</td>
					</tr>";
				}
				
				echo "</table></div>";
			} else {
				echo "<p><em>No arguments passed</em></p>";
			}
		}
		
		echo "</div>";
	} else {
		echo "<p><em>No stack trace</em></p>";
	}
?>
		</div>
		
		<div class="footer">
			Powered by <a href="http://talkwork.sourceforge.net/">Talkwork</a>.
			<span class="right"><a href="https://github.com/garrettw/talkwork">View on Github</a></span>
		</div>
	</div>
</body>
</html>