<?php

namespace EssentialDots\Mink\Driver;

use Behat\Mink\Session,
	Behat\Mink\Element\NodeElement,
	Behat\Mink\Exception\DriverException,
	Behat\Mink\Exception\UnsupportedDriverActionException;

use EssentialDots\Mink\Driver\Webkit\Browser;

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

class WebkitDriver implements \Behat\Mink\Driver\DriverInterface {
	/* @var boolean */
	private $started = false;

	/* @var Webkit\Browser */
	private $browser;

	/* @var Session */
	private $session;


	public function getBrowser() {
		return $this->browser;
	}

	/**
	 * @param Browser $browser
	 */
	public function __construct(Browser $browser) {
		$this->browser = $browser;
	}

	/**
	 * Sets driver's current session.
	 *
	 * @param   \Behat\Mink\Session $session
	 */
	public function setSession(Session $session) {
		$this->session = $session;
	}

	/**
	 * Starts driver.
	 */
	public function start() {
		$this->started = true;
		$this->browser->start();
	}

	/**
	 * Checks whether driver is started.
	 *
	 * @return  Boolean
	 */
	public function isStarted() {
		return $this->started;
	}

	/**
	 * Stops driver.
	 */
	public function stop() {
		$this->started = false;
		$this->browser->stop();
	}

	/**
	 * Resets driver.
	 */
	public function reset() {
		$this->browser->reset();
	}

	/**
	 * Visit specified URL.
	 *
	 * @param   string $url url of the page
	 */
	public function visit($url) {
		$this->browser->visit($url);
	}

	/**
	 * Returns current URL address.
	 *
	 * @return  string
	 */
	public function getCurrentUrl() {
		return $this->browser->currentUrl();
	}

	/**
	 * Reloads current page.
	 */
	public function reload() {
		$this->browser->executeScript('location.reload()');
	}

	/**
	 * Moves browser forward 1 page.
	 */
	public function forward() {
		$this->browser->executeScript('history.forward()');
	}

	/**
	 * Moves browser backward 1 page.
	 */
	public function back() {
		$this->browser->executeScript('history.back()');
	}

	/**
	 * Sets HTTP Basic authentication parameters
	 *
	 * @param   string|false $user user name or false to disable authentication
	 * @param   string $password password
	 */
	public function setBasicAuth($user, $password) {
		$this->browser->setHeader("Authorizatoin", base64_encode($user . ":" . $password));
	}

	/**
	 * Switches to specific browser window.
	 *
	 * @param string $name window name (null for switching back to main window)
	 *
	 * @throws UnsupportedDriverActionException
	 */
	public function switchToWindow($name = null) {
		throw new UnsupportedDriverActionException('Window management is not supported by %s', $this);
	}

	/**
	 * Switches to specific iFrame.
	 *
	 * @param string $name iframe name (null for switching back)
	 *
	 * @throws UnsupportedDriverActionException
	 */
	public function switchToIFrame($name = null) {
		throw new UnsupportedDriverActionException('iFrame management is not supported by %s', $this);
	}

	/**
	 * Sets specific request header on client.
	 *
	 * @param   string $name
	 * @param   string $value
	 */
	public function setRequestHeader($name, $value) {
		$this->browser->setHeader($name, $value);
	}

	/**
	 * Returns last response headers.
	 *
	 * @return  array
	 */
	public function getResponseHeaders() {
		return $this->browser->responseHeader();
	}

	/**
	 * Sets cookie.
	 *
	 * @param   string $name
	 * @param   string $value
	 */
	public function setCookie($name, $value = null) {
		$this->browser->setCookies($name, $value);
	}

	/**
	 * Returns cookie by name.
	 *
	 * @param string $name
	 * @return null|string|void
	 * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
	 */
	public function getCookie($name) {
		$cookies = $this->browser->getCookies();
		//@todo parse cookies and obtain 1 cookie with specified name
		throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
	}

	/**
	 * Returns last response status code.
	 *
	 * @return  integer
	 */
	public function getStatusCode() {
		return $this->browser->statusCode();
	}

	/**
	 * Returns last response content.
	 *
	 * @return  string
	 */
	public function getContent() {
		return $this->browser->body();
	}

	/**
	 * Finds elements with specified XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  array           array of Behat\Mink\Element\NodeElement
	 */
	public function find($xpath) {
		$nodes = $this->browser->find($xpath);

		$elements = array();
		foreach ($nodes as $offset => $node_id) {
			$elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $offset + 1), $this->session);
		}

		return $elements;
	}

	/**
	 * Returns element's tag name by it's XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  string
	 */
	public function getTagName($xpath) {
		$this->browser->getTagName($xpath);
	}

	/**
	 * Returns element's text by it's XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  string
	 */
	public function getText($xpath) {
		$node = $this->browser->findOne($xpath);
		return $this->browser->invoke("text", $node);
	}

	/**
	 * Returns element's html by it's XPath query.
	 *
	 * @param string $xpath
	 * @return string|void
	 * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
	 */
	public function getHtml($xpath) {
		// TODO: Implement getHtml() method.
		throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
	}

	/**
	 * Returns element's attribute by XPath
	 *
	 * @param string $xpath
	 * @param string $attr
	 * @return mixed
	 */
	public function getAttribute($xpath, $attr) {
		return $this->browser->invoke("attribute",
			$this->browser->findOne($xpath),
			$attr);
	}

	/**
	 * Returns element's value by it's XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  mixed
	 */
	public function getValue($xpath) {
		return $this->browser->invoke("value", $this->browser->findOne($xpath));
	}

	/**
	 * Sets element's value by it's XPath query.
	 *
	 * @param   string $xpath
	 * @param   string $value
	 */
	public function setValue($xpath, $value) {
		$this->browser->setValue($xpath, $value);
	}

	/**
	 * Checks checkbox by it's XPath query.
	 *
	 * @param   string $xpath
	 * @return mixed
	 */
	public function check($xpath) {
		$node = $this->browser->findOne($xpath);
		return $this->browser->invoke("set", $node, "true");
	}

	/**
	 * Unchecks checkbox by it's XPath query.
	 *
	 * @param   string $xpath
	 * @return mixed
	 */
	public function uncheck($xpath) {
		$node = $this->browser->findOne($xpath);
		return $this->browser->invoke("set", $node, "false");
	}

	/**
	 * Checks whether checkbox checked located by it's XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  Boolean
	 */
	public function isChecked($xpath) {
		$node = $this->browser->findOne($xpath);
		return $this->browser->invoke("attribute", $node, "checked");
	}

	/**
	 * Selects option from select field located by it's XPath query.
	 *
	 * @param   string $xpath
	 * @param   string $value
	 * @param   Boolean $multiple
	 */
	public function selectOption($xpath, $value, $multiple = false) {
		$path = $xpath . "/option[(text()='$value' or @value='$value')]";

		$node = $this->browser->findOne($path);
		$this->browser->invoke("selectOption", $node);
	}

	/**
	 * Clicks button or link located by it's XPath query.
	 *
	 * @param   string $xpath
	 */
	public function click($xpath) {
		$this->browser->click($xpath);
	}

	/**
	 * Double-clicks button or link located by it's XPath query.
	 *
	 * @param   string $xpath
	 */
	public function doubleClick($xpath) {
		$this->browser->trigger($xpath, "dblclick");
	}

	/**
	 * Right-clicks button or link located by it's XPath query.
	 *
	 * @param   string $xpath
	 */
	public function rightClick($xpath) {
		$this->browser->trigger($xpath, "contextmenu");
	}

	/**
	 * Attaches file path to file field located by it's XPath query.
	 *
	 * @param   string $xpath
	 * @param   string $path
	 */
	public function attachFile($xpath, $path) {
		$this->browser->setValue($xpath, $path);
	}

	/**
	 * Checks whether element visible located by it's XPath query.
	 *
	 * @param   string $xpath
	 *
	 * @return  Boolean
	 */
	public function isVisible($xpath) {
		$node = $this->browser->findOne($xpath);
		return $this->browser->invoke("visible", $node);
	}

	/**
	 * Simulates a mouse over on the element.
	 *
	 * @param   string $xpath
	 */
	public function mouseOver($xpath) {
		$this->browser->trigger($xpath, "mouseover");
	}

	/**
	 * Brings focus to element.
	 *
	 * @param   string $xpath
	 */
	public function focus($xpath) {
		$this->browser->trigger($xpath, "focus");
	}

	/**
	 * Removes focus from element.
	 *
	 * @param   string $xpath
	 */
	public function blur($xpath) {
		$this->browser->trigger($xpath, "blur");
	}

	/**
	 * Presses specific keyboard key.
	 *
	 * @param   string $xpath
	 * @param   mixed $char could be either char ('b') or char-code (98)
	 * @param   string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
	 */
	public function keyPress($xpath, $char, $modifier = null) {
		$node = $this->browser->findOne($xpath);
		$alt = ($modifier == "alt") ? "true" : "false";
		$ctrl = ($modifier == "ctrl") ? "true" : "false";
		$meta = ($modifier == "meta") ? "true" : "false";
		$shift = ($modifier == "shift") ? "true" : "false";
		$charCode = ord($char);

		$this->getBrowser()->invoke("keypress", $node, $alt, $ctrl, $shift, $meta, 0, $charCode);
	}

	/**
	 * Pressed down specific keyboard key.
	 *
	 * @param   string $xpath
	 * @param   mixed $char could be either char ('b') or char-code (98)
	 * @param   string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
	 */
	public function keyDown($xpath, $char, $modifier = null) {
		$this->keyImpl('keydown', $xpath, $char, $modifier);
	}

	/**
	 * Pressed up specific keyboard key.
	 *
	 * @param   string $xpath
	 * @param   mixed $char could be either char ('b') or char-code (98)
	 * @param   string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
	 */
	public function keyUp($xpath, $char, $modifier = null) {
		$this->keyImpl('keyup', $xpath, $char, $modifier);
	}

	/**
	 * @param string $type keydown or keyup
	 * @param string $xpath
	 * @param string $char keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
	 * @param string $modifier
	 */
	protected function keyImpl($type, $xpath, $char, $modifier = null) {
		$altKey = ($modifier == 'alt') ? 'true' : 'false';
		$ctrlKey = ($modifier == 'ctrl') ? 'true' : 'false';
		$shiftKey = ($modifier == 'shift') ? 'true' : 'false';
		$metaKey = ($modifier == 'meta') ? 'true' : 'false';
		$charCode = ord($char);

		$script = <<<JS
(function(){
    var itr = document.evaluate("$xpath", document, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
    var node;
    var results = [];

    while (node = itr.iterateNext()) {
        results.push(node);
    }

    if (results.length > 0) {
        var target = results[0];
        var eventObject = document.createEvent("Events");
        eventObject.initEvent('$type', true, true);

        eventObject.window   = window;
        eventObject.altKey   = $altKey;
        eventObject.ctrlKey  = $ctrlKey;
        eventObject.shiftKey = $shiftKey;
        eventObject.metaKey  = $metaKey;
        eventObject.keyCode  = 0;
        eventObject.charCode = $charCode;
        target.dispatchEvent(eventObject);
    }

})();
JS;
		$this->executeScript($script);
	}

	/**
	 * Drag one element onto another.
	 *
	 * @param   string $sourceXpath
	 * @param   string $destinationXpath
	 */
	public function dragTo($sourceXpath, $destinationXpath) {
		$source = $this->browser->findOne($sourceXpath);
		$dest = $this->browser->findOne($destinationXpath);

		$this->browser->invoke("dragTo", $source, $dest);
	}

	/**
	 * Executes JS script.
	 *
	 * @param   string $script
	 */
	public function executeScript($script) {
		$this->browser->executeScript($script);
	}

	/**
	 * Evaluates JS script.
	 *
	 * @param   string $script
	 *
	 * @return  mixed           script return value
	 */
	public function evaluateScript($script) {
		return $this->browser->evaluateScript(preg_replace('/^\s*return\s*/msU', '', $script));
	}

	/**
	 * Waits some time or until JS condition turns true.
	 *
	 * @param   integer $time time in milliseconds
	 * @param   string $condition JS condition
	 */
	public function wait($time, $condition) {
		$script = "$condition";
		$start = 1000 * microtime(true);
		$end = $start + $time;

		while (1000 * microtime(true) < $end &&
			!$this->browser->evaluateScript($script)) {
			sleep(0.1);
		}

	}

	/**
	 * save image
	 *
	 * @param $path
	 * @param int $width
	 * @param int $height
	 */
	public function render($path, $width = 1024, $height = 100) {
		$this->browser->render($path, $width, $height);
	}

}