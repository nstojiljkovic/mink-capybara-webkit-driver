<?php

namespace EssentialDots\Mink\Driver\Webkit;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nikola Stojiljkovic, Essential Dots d.o.o. Belgrade
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Browser {
	/* @var resource server socket */
	protected $server;

	/**
	 * @var \EssentialDots\Headless\Headless
	 */
	protected $headless;

	/* @var int */
	protected $port;

	/* @var resource proc_opened process */
	protected $process;

	/* @var string $binPath */
	protected $binPath = 'webkit_server';

	/* @var bool $ignoreSSLErrors */
	protected $ignoreSSLErrors = false;

	/**
	 * @var array
	 */
	protected $consoleLog = array();

	/**
	 * @var string
	 */
	protected $version = null;

	/**
	 * @param string $binPath
	 * @param bool $ignoreSSLErrors
	 */
	public function __construct($binPath = 'webkit_server', $ignoreSSLErrors = false) {
		$this->binPath = $binPath;
		$this->ignoreSSLErrors = $ignoreSSLErrors;
		$this->headless = new \EssentialDots\Headless\Headless();
	}

	/**
	 * start server and connect
	 */
	public function start() {
		if (!is_resource($this->process)) {
			$this->headless->start();
			$this->startServer();
			$this->connect();
			if ($this->ignoreSSLErrors) {
				$this->command("IgnoreSslErrors");
			}
		}
	}

	/**
	 * stop server and disconnect
	 */
	public function stop() {
		$this->headless->destroy();
		$this->killServer();
		$this->disconnect();
	}

	/**
	 * disconnect
	 */
	public function disconnect() {
		if (is_resource($this->server)) {
			fclose($this->server);
		}
	}

	/**
	 * return current server port
	 *
	 * @return int
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * return current url
	 *
	 * @return string
	 */
	public function currentUrl() {
		return $this->command("CurrentUrl");
	}

	/**
	 * get tag name
	 *
	 * @param $xpath
	 */
	public function getTagName($xpath) {
		$this->invoke("tagName", $this->findOne($xpath));
	}

	/**
	 * set form value
	 *
	 * @param $xpath
	 * @param $value
	 */
	public function setValue($xpath, $value) {
		$node = $this->findOne($xpath);
		$this->invoke("set", $node, $value);
	}

	/**
	 * save image
	 *
	 * @param $path
	 * @param int $width
	 * @param int $height
	 */
	public function render($path, $width = 1024, $height = 680) {
		$this->command("Render", array($path, intval($width), intval($height)));
	}

	/**
	 * visit specified url
	 *
	 * @param $url
	 * @return int result status
	 */
	public function visit($url) {
		return $this->command("Visit", array($url));
	}

	/**
	 * @param bool $throwExceptionOnJSError
	 * @throws \Behat\Mink\Exception\DriverException
	 */
	protected function updateConsoleLog($throwExceptionOnJSError = false) {
		if ($throwExceptionOnJSError) {
			$throwException = false;
			$newLog = $this->consoleMessage();
			$logDiff = array();
			for ($i = count($this->consoleLog); $i < count($newLog); $i++) {
				$logDiff[] = $newLog[$i];
				if ($newLog != null && is_array($newLog) && array_key_exists('line_number', $newLog[$i]) && array_key_exists('message', $newLog[$i]) && strpos($newLog[$i]['message'], 'Error:') !== FALSE) {
					$throwException = true;
				}
			}
			if ($throwException) {
				throw new \Behat\Mink\Exception\DriverException('JavaScript error occured: ' . print_r($logDiff, 1));
			}
		}

		$this->consoleLog = $this->consoleMessage();
	}

	/**
	 * dunno
	 *
	 * @return array
	 */
	public function consoleMessage() {
		return json_decode($this->command("ConsoleMessages"), true);
	}

	/**
	 * returns current response header.
	 *
	 * @return array
	 */
	public function responseHeader() {
		$result = array();
		foreach (explode("\n", $this->command("Headers")) as $line) {
			list($key, $value) = explode(": ", $line);
			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * set http header
	 *
	 * @param $key
	 * @param $value
	 */
	public function setHeader($key, $value) {
		$this->command("Header", array($key, $value));
	}

	/**
	 * starts webkit_server.
	 *
	 * webkit_server will listen random port number
	 *
	 * @throws \RuntimeException
	 */
	public function startServer() {
		$pipes = array();
		$server_path = $this->binPath;

		if (!file_exists($this->binPath)) {
			throw new \RuntimeException("Binary webkit_server does not exist.");
		}

		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w"),
		);

		$process = proc_open($server_path, $descriptorspec, $pipes);
		if (is_resource($process)) {
			$data = fgets($pipes[1]);
			$this->port = $this->discoverServerPort($data);
			$this->process = $process;
		} else {
			throw new \RuntimeException("Couldn't launch webkit_server");
		}

		/* always terminate webkit_server */
		register_shutdown_function(array($this, "registerShutdownHook"));
	}

	/**
	 * find one content with specified xpath
	 * @param $xpath
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function findOne($xpath) {
		$nodes = $this->find($xpath);
		if (count($nodes)) {
			return array_shift($nodes);
		} else {
			throw new \Exception(
				"element not found"
			);
		}

	}

	/**
	 * invoke js function on capybara webkit server.
	 *
	 * @return mixed
	 */
	public function invoke() {
		$arguments = func_get_args();
		if (version_compare($this->getVersion(), '1.1.0', '<')) {
			return $this->command("Node", $arguments);
		} else {
			$invokeCommand = array_shift($arguments);
			array_unshift($arguments, "true"); // set allowUnattached to TRUE
			array_unshift($arguments, $invokeCommand);
			return $this->command("Node", $arguments);
		}
	}

	/**
	 * @param string $handle
	 * @param int $width
	 * @param int $height
	 */
	public function resizeWindow($width, $height, $handle = null) {
		if (version_compare($this->getVersion(), '1.2', '>=')) {
			return $this->command('ResizeWindow', array($handle, intval($width), intval($height)));
		} else {
			return $this->command('ResizeWindow', array(intval($width), intval($height)));
		}
	}

	/**
	 * returns http status
	 *
	 * @return int
	 */
	public function statusCode() {
		return (int)$this->command("Status");
	}

	/**
	 * reload and return current body
	 *
	 * @return string
	 */
	public function source() {
		return $this->command("Source");
	}

	/**
	 * trigger event
	 *
	 * @param $xpath
	 * @param $event
	 * @return bool
	 */
	public function trigger($xpath, $event) {
		$nodes = $this->find($xpath);
		$node = array_shift($nodes);
		if (!empty($node)) {
			$this->invoke("trigger", $node, $event);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Reset browser session
	 *
	 * @return void
	 */
	public function reset() {
		$this->command("Reset");
	}

	/**
	 * find on capybara webkit.
	 *
	 * @param $query
	 * @return array internal node ids.
	 */
	public function find($query) {
		$ret = $this->command("FindXpath", array($query));
		if (empty($ret)) {
			return array();
		}

		return explode(",", $ret);
	}

	/**
	 * obtain current frame buffer as string.
	 *
	 * @return string
	 */
	public function body() {
		return $this->command("Body");
	}

	/**
	 * evaluate specified js and returns it result as json object
	 *
	 * @param array $script
	 */
	public function evaluateScript($script) {
		$this->updateConsoleLog();
		$json = $this->command("Evaluate", array($script));
		$this->updateConsoleLog(true);
		$result = json_decode("[{$json}]", true);
		return $result[0];
	}

	/**
	 * execute javascript
	 *
	 * @param $script
	 * @return mixed
	 */
	public function executeScript($script) {
		$this->updateConsoleLog();
		$result = $this->command("Execute", array($script));
		$this->updateConsoleLog(true);
		return $result;
	}

	/**
	 * connect to spawned webkit_serer.
	 *
	 * @throws \RuntimeException
	 */
	protected function connect() {
		$server = stream_socket_client("tcp://localhost:{$this->port}", $errno, $errstr, 5);
		if (is_resource($server)) {
			$this->server = $server;
		} else {
			throw new \RuntimeException("could not connect to webkit_server");
		}
	}

	/**
	 * send command to webkit_server
	 *
	 * @param $command
	 * @param array $args
	 * @return mixed the result
	 */
	public function command($command, $args = array()) {
		fwrite($this->server, $command . "\n");
		fwrite($this->server, count($args) . "\n");

		foreach ($args as $arg) {
			fwrite($this->server, strlen($arg) . "\n");
			fwrite($this->server, $arg);
		}
		$this->check();
		return $this->readResponse();
	}

	/**
	 * clear cookies
	 *
	 */
	public function clearCookies() {
		$this->command("ClearCookies");
	}

	/**
	 * dunno
	 *
	 * @param null $frame_id_or_index
	 * @return mixed
	 */
	public function frameFocus($frame_id_or_index = null) {
		if (is_string($frame_id_or_index)) {
			return $this->command("FrameFocus", array("", $frame_id_or_index));
		} else if ($frame_id_or_index) {
			return $this->command("FrameFocus", array($frame_id_or_index));
		} else {
			return $this->command("FrameFocus");
		}
	}

	/**
	 * click
	 *
	 * @param $xpath
	 */
	public function click($xpath) {
		$this->invoke("leftClick", $this->findOne($xpath));
	}

	/**
	 * invoke mouse up event
	 *
	 * @param $xpath
	 */
	public function mouseup($xpath) {
		$this->invoke("mouseup", $this->findOne($xpath));
	}


	/**
	 * invoke mouse down event
	 *
	 * @param $xpath
	 */
	public function mousedown($xpath) {
		$this->invoke("mousedown", $this->findOne($xpath));
	}

	/**
	 * returns element visibility
	 *
	 * @param $xpath
	 * @return bool
	 */
	public function visible($xpath) {
		return (bool)$this->invoke("visible", $this->findOne($xpath));
	}

	/**
	 * set proxy setting
	 *
	 * @param array $options
	 */
	public function setProxy($options = array()) {
		$options = array_merge(array(
				"host" => "localhost",
				"port" => 0,
				"user" => "",
				"pass" => "",
			),
			$options
		);
		$this->command("SetProxy", array(
			$options['host'],
			$options['port'],
			$options['user'],
			$options['pass']
		));
	}

	/**
	 * clear proxy setting
	 */
	public function clearProxy() {
		$this->command("SetProxy");
	}

	/**
	 * set cookies
	 *
	 * @param string $cookie
	 */
	public function setCookies($cookie) {
		$this->command("setCookies", array($cookie));
	}

	/**
	 * get cookies.
	 *
	 * @todo parse cookie string
	 *
	 * @return array
	 */
	public function getCookies() {
		$result = array();
		foreach (explode("\n", $this->command("GetCookies")) as $line) {
			$line = trim($line);
			if (!empty($line)) {
				$result[] = $line;
			}
		}
		return $result;
	}

	/**
	 * remove instance
	 *
	 */
	public function __destruct() {
		$this->killServer();
	}

	/**
	 * terminate current webkit_server process
	 *
	 * @return void
	 */
	protected function killServer() {
		if (is_resource($this->process)) {
			proc_terminate($this->process);
		}
	}

	/**
	 * shutdown hook
	 *
	 * prevents unterminated webkit_server process.
	 */
	public function registerShutdownHook() {
		$this->killServer();
	}

	/**
	 * check webkit_server response
	 *
	 * @throws \Exception
	 */
	protected function check() {
		$error = trim(fgets($this->server));
		if ($error != "ok") {
			throw new \Exception($this->readResponse($this->server));
		}
	}

	/**
	 * @return string
	 */
	protected function readResponse() {
		$data = "";
		$nread = trim(fgets($this->server));

		if ($nread == 0) {
			return $data;
		}

		$read = 0;
		while ($read < $nread) {
			$tmp = fread($this->server, $nread);
			$read += strlen($tmp);
			$data .= $tmp;
		}
		return $data;
	}

	/**
	 * @param $line
	 * @return mixed
	 * @throws \RuntimeException
	 */
	protected function discoverServerPort($line) {
		if (preg_match('/listening on port: (\d+)/', $line, $matches)) {
			return (int)$matches[1];
		} else {
			throw new \RuntimeException("couldn't find server port");
		}
	}

	/**
	 * @return string
	 */
	protected function getVersion() {
		if (!$this->version) {
			$matches = null;
			if (preg_match('|capybara-webkit-([^/]*)/|msU', $this->binPath, $matches)) {
				$this->version = $matches[1];
			} else {
				// should not go here
				$this->version = '0.0.0';
			}
		}
		return $this->version;
	}
}