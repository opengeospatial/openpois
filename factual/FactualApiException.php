<?php

/**
 * Represents an Exception that happened while communicating with Factual.
 * Includes information about the request that triggered the problem.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class FactualApiException extends Exception {
	private $requestUrl; //string
	private $requestMethod; //string
	private $status;
	private $version;
	private $errorType;

	public function __construct($e) {
		$response = $e->getMessage();
		$struct = json_decode(strstr($response, "{"), true);
		$this->message = $struct['message'];
		$this->status = $struct['status'];
		$this->version = $struct['version'];
		$this->errorType = $struct['error_type'];
		$this->code = substr($response, strpos($response, "code") + 5, 3);
	}

	public function requestUrl($url) {
		$this->requestUrl = $url;
		return $this;
	}

	public function requestMethod($method) {
		$this->requestMethod = $method;
		return $this;
	}

	/**
	 * @return the URL used to make the offending request to Factual.
	 */
	public function getRequestUrl() {
		return $this->requestUrl;
	}

	/**
	 * @return the URL used to make the offending request to Factual.
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return the URL used to make the offending request to Factual.
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @return the URL used to make the offending request to Factual.
	 */
	public function getErrorType() {
		return $this->errorType;
	}

	/**
	 * @return the HTTP request method used to make the offending request to Factual.
	 */
	public function getRequestMethod() {
		return $this->requestMethod;
	}

}
?>