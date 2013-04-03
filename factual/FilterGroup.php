<?php


/**
 * Represents a group of Filters as one Filter.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class FilterGroup implements FactualFilter {
	private $filters = array (); //array
	private $op = "\$and"; //string

	/**
	 * Constructor. Defaults logic to AND.
	 * @param array filter Filter objects. Filters can be passed as parameter or assigned later
	 */
	public function __construct($filters = null) {
		if ($filters) {
			$this->filters = $filters;
		}
	}

	/**
	 * Sets this FilterGroup's logic, e.g., "$or".
	 */
	public function op($op) {
		$this->op = $op;
		return $this;
	}

	/**
	 * Sets this FilterGroup's logic to be OR.
	 */
	public function asOR() {
		$this->op = "\$or";
	}

	public function add($filter) {
		$this->filters[] = $filter;
	}

	/**
	 * Produces JSON representation for this FilterGroup
	 * <p>
	 * For example:
	 * <pre>
	 * {"$and":[{"first_name":{"$eq":"Bradley"}},{"region":{"$eq":"CA"}},{"locality":{"$eq":"Los Angeles"}}]}
	 * </pre>
	 * @return string 
	 */
	public function toJsonStr() {
		return "{\"" . $this->op . "\":[" . $this->logicJsonStr() . "]}";
	}

	/**
	 * @return string
	 */
	private function logicJsonStr() {
		$logics = array ();
		foreach ($this->filters as $filter) {
			$logics[] = $filter->toJsonStr();
		}
		return implode(",", $logics);
	}

}
?>