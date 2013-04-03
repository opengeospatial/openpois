<?php


/**
 * Provides basic geocoder via Yahoo Placefinder YQL web service -- unsigned access with relatively low limits
 * Cobbled together from https://github.com/twbell/GPLplanet
 * Not a supported part of the Factual API wrapper; included to provide example hooks into Factual object
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */

class GeocoderWrapper {

	protected $endPoint = 'http://query.yahooapis.com/v1/public/yql'; //public query point						
	protected $flags = "G"; //placefinder geocoder flags
	protected $lastQuery = 0; //timestamp of last web query, used to control calls-per-second 	 
	protected $webServiceWait = 5; //webservice wait between calls in seconds (0 = no wait)
	protected $checkServiceStatus = true; //checks status of service if no results received (prevents hammering)

	/**
	 * Sets whether YQL return vars are checked for exceeding service limit (default = true)
	 * @param bool check 
	 * @return true
	 */
	public function setCheckServiceStatus($check) {
		$this->checkServiceStatus = $check;
		return true;
	}

	/**
	* Pauses script appropriate time before calling webservice again
	* @param timestamp unix timestamp
	* @return array
	*/
	protected function webserviceWait() {
		if ($this->lastQuery) {
			$timeSince = microtime(true) - $this->lastQuery;
			if ($timeSince < $this->webServiceWait) {
				$wait = $this->webServiceWait - $timeSince;
				usleep(($wait) * 1000000);
			}
		}
		$this->lastQuery = microtime(true);
		return true;
	}

	/**
	* Geocodes address string or placename
	* @param string q 
	* @return array
	*/
	public function geocode($q) {
		$q = "SELECT * FROM geo.placefinder WHERE text=\"" . $q . "\"";
		if ($this->flags) {
			$q .= " AND flags=\"" . $this->flags . "\"";
		}
		//hit geocode service
		$res = $this->query($q);
		if (!$res) {
			return false;
		}
		if ($res->query->count > 0) {
			if (is_object($res->query->results->Result)) {
				$returnVals = get_object_vars($res->query->results->Result);
				return $returnVals;
			} else {
				return array (); //empty result
			}
		} else {
			return array (); //no result
		}
	}

	/**
	 * Converts webservice place result (json converted) into an array for easier handling
	 * http://www.wait-till-i.com/2010/09/22/the-annoying-thing-about-yqls-json-output-and-the-reason-for-it/
	 * @param array place json object returned by geoplanet
	 * @return array
	 */
	protected function serviceToArray($place) {
		$place = get_object_vars($place);
		foreach ($place as $key => $value) {
			if (is_object($place[$key])) {
				$place[$key] = get_object_vars($value);
			}
		}
		foreach ($place['boundingBox'] as $key => $value) { //nested one further layer down
			if (is_object($place['boundingBox'][$key])) {
				$place['boundingBox'][$key] = get_object_vars($value);
			}
		}
		return $place;
	}

	/**
	* Reverse geocodes long/lat to the smallest bounding WOEID
	* @param real lon Decimal Longitude
	* @param real lat Decimal Latitude
	* @return array single result
	*/
	public function reverseGeocode($lon, $lat) {
		$q = "SELECT * from geo.placefinder WHERE text=\"" . $lat . "," . $lon . "\" AND gflags=\"R\"";
		if ($this->flags) {
			$q .= " AND flags=\"" . $this->flags . "\"";
		}
		//hit geocode service
		$res = $this->query($q);
		if ($res->query->count > 0) {
			$returnVals = get_object_vars($res->query->results->Result);
			if ($returnVals['quality'] == 99) {
				$returnVals['line1'] = ""; //strip out coordinate pair as line1
			}
			return $returnVals;
		} else {
			return array ();
		}
	}

	/**
	 * Assembles and runs YQL Query
	 * @param string qString Query string
	 * @param array aUserVars user-defined key/value parameters to be taked onto end of URL
	 * @return object json object
	 */
	public function query($qString, $aUserVars = "") {
		if (!$qString) {
			$this->logMsg(__METHOD__ . " No query string passed to YQL");
			return false;
		}
		//build variable array
		$aVars = array (
			'format' => 'json'
		);
		//add variables from parameter
		if ($aUserVars && is_array($aUserVars)) {
			$aVars = array_merge($aVars, $aUserVars);
		}
		//combine keys and values
		foreach ($aVars as $key => $value) {
			if ($value) {
				$aVarComb[] = $key . "=" . urlencode($value);
			}
		}
		unset ($aVars);
		//create data string
		$sData = implode("&", $aVarComb);
		unset ($aVarComb);
		$endPoint = $this->endPoint . "?q=" . urlencode($qString) . "&" . $sData;
		$this->webserviceWait(); //pause as required before calling webservice again
		$http_response_header = array();
		@ $result = file_get_contents($endPoint);
		if (!$result) {
			throw new Exception(" YQL Error on " . $qString . ": " . $http_response_header[0]);
			return false;
		}
		$result = json_decode($result);
		//checks results for no love, calls diagnostics, and bails on error 999
		if ($this->checkServiceStatus) {
			if ($result->query->count === 0) {
				//run same query with diagnostics
				$check = json_decode(file_get_contents($endPoint . "&diagnostics=true"), true); //as array; obj vars w/ dashes fail
				if ($check['query']['diagnostics']['url']['http-status-code'] == 999) {
					throw new Exception("YQL error 999: limit appears to have been reached");
					return false;
				}
			}
		}
		return $result;
	}

}
?>
