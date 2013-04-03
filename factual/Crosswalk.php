<?php


/**
 * Represents a single Crosswalk record from Factual.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class Crosswalk {
	private $factual_id; //string
	private $url; //string
	private $_namespace; //string
	private $namespaceId; //string

	/**
	 * Constructor takes array of atributes for single crosswalk result
	 */
	public function __construct($attrs) {
		$this->factualId = $attrs['factual_id'];
		$this->url = $attrs['url'];
		$this->_namespace = $attrs['namespace'];
		$this->namespaceId = $attrs['namespace_id'];
	}

	/**
	 * @return string
	 */
	public function getFactualId() {
		return $this->factualId;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getNamespace() {
		return $this->_namespace;
	}

	/**
	 * @return string
	 */
	public function getNamespaceId() {
		return $this->namespaceId;
	}

	/**
	 * @return string
	 */
	public function toString() {
		return "[Crosswalk: " . "factualId=" . $this->factual_id . ", url=" . $this->url . ", namespace=" . $this->_namespace . ", namespaceId=" . $this->namespaceId . "]";
	}

}
?>
