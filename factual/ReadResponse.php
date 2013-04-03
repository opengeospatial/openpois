<?php

/**
 * Represents the response from running a fetch request against Factual, such as
 * a geolocation based query for specific places entities.
  * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class ReadResponse extends FactualResponse {
	protected $entityType = null;


	/*
	 * Results as array of PHP objects (experimental)
	 * @return array Array of objects

	public function getObjects() {
		if ($this->entityType) {
			return parent :: asObjects($this->entityType);
		} else {
			throw new Exception("Method " . __METHOD__ . " called but no entity type set");
		}
	}
	 */
}
?>