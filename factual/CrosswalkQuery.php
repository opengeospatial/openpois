<?php


/**
 * Represents a Factual Crosswalk query.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class CrosswalkQuery extends FactualQuery {
	private $factualId; //string
	private $limit; //int
	private $_namespace; //string
	private $namespaceId; //string
	private $only = array ();

	/**
	 * Whether this lib must perform URL encoding.
	 * Set to avoid double or absent encoding
	 */
	const URLENCODE = true;

	/**
	 * Adds the specified Factual ID to this Query. Returned Crosswalk data will
	 * be for only the entity associated with the Factual ID.
	 * @param string factualId A unique Factual ID.
	 * @return object This CrosswalkQuery
	 */
	public function factualId($factualId) {
		$this->factualId = $factualId;
		return $this;
	}

	//Overides inherited function
	public function sortDesc($field){
		return $this; 
	}

	//Overides inherited function
	public function sortAsc($field) {
		return $this;
	}

	/**
	 * Adds the specified <tt>limit</tt> to this Query. The amount of returned
	 * Crosswalk records will not exceed this limit.
	 * @param int limit Number of records to return
	 * @return object This CrosswalkQuery
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * The namespace to search for a third party ID within.
	 * @param string namespace The namespace to search for a third party ID within.
	 * @return object This CrosswalkQuery
	 * @internal renamed from 'namespace' due to reserved word
	 */
	public function _namespace($namespace) {
		$this->_namespace = $namespace;
		return $this;
	}

	/**
	 * The id used by a third party to identify a place.
	 * You must also supply <tt>namespace</tt> via {@link #namespace(String)}.
	 * @param string namespaceId The id used by a third party to identify a place.
	 * @return object This CrosswalkQuery
	 */
	public function namespaceId($namespaceId) {
		$this->namespaceId = $namespaceId;
		return $this;
	}

	/**
	 * Restricts the results to only return ids for the specified namespace(s).
	 * @param mixed namespaces as comma-delineated strings, or array of namespace names
	 * @return object This CrosswalkQuery
	 */
	public function only($namespaces) {
		if (!is_array($namespaces)) {
			$namespaces = explode(",", $namespaces);
		}
		foreach ($namespaces as $ns) {
			$this->only[] = $ns;
		}
		return $this;
	}

	/**
	 * Converts this entity to a URL
	 * @return string
	 */
	public function toUrlQuery() {
		$temp[] = $this->urlPair("factual_id", $this->factualId);
		$temp[] = ($this->limit > 0 ? $this->urlPair("limit", $this->limit) : null);
		$temp[] = $this->urlPair("namespace", $this->_namespace);
		$temp[] = $this->urlPair("namespace_id", $this->namespaceId);
		$temp[] = $this->urlPair("only", $this->onlysOrNull());
		//remove nulls
		$temp = array_filter($temp);
		//join and return
		return implode("&", $temp);
	}

	private function onlysOrNull() {
		if (!empty ($this->only)) {
			$this->only = array_filter($this->only);
			return implode(",", $this->only);
		} else {
			return null;
		}
	}

	/**
	 * @param string name Name
	 * @param string val Value
	 * @return string
	 * @internal Not sure why val is obj in Java version in return line
	 */
	private function urlPair($name, $val) {
		if ($val != null) {
			try {
				if (self :: URLENCODE) {
					return $name . "=" . urlencode($val);
				} else {
					return $name . "=" . $val;
				}
			} catch (Exception $e) {
				throw $e;
			}
		} else {
			return null;
		}
	}

}
?>
