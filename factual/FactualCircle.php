<?php

/**
 * Represents a geographic sub query confining results to a circle.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class FactualCircle {
	private $lat;
	private $lon;
	private $meters;

	/**
	 * Constructs a geographic Circle representation.
	 * @param real lat the latitude of the center of this Circle.
	 * @param real lon the longitude of the center of this Circle.
	 * @param int metersRadius the radius, in meters, of this Circle.
	 */
	public function __construct($lat, $lon, $meters) {
		if (!is_numeric($lat) || !is_numeric($lon)){
			throw new Exception("Cannot create FactualCircle: bad lat/lon parameters: lat='".$lat."',lon='".$lon."'");
			return false;
		}
		$this->lat = $lat;
		$this->lon = $lon;
		$this->meters = $meters;
	}

	/**
	 * Returns JSON component of point-radius query
	 * @return string 
	 */
	public function toJsonStr() {
		return "{\"\$circle\":{\"\$center\":[" . $this->lat . "," . $this->lon . "],\"\$meters\":" . $this->meters . "}}";
	}

}
?>
