<?php


/**
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
*/

class FactualColumnSchema {
	public $name; //string
	public $description; //string
	public $faceted; //bool
	public $sortable; //bool
	public $label; //string
	public $datatype; //string
	public $searchable; //bool

	/**
	 * Constructor. Maps raw column schema data into a new ColumnSchema.
	 * @param map Array of schema as provided by Factual.
	 */
	public function __construct($map) {
		$this->name = (string) $map['name'];
		$this->description = (string) $map['description'];
		$this->label = (string) $map['label'];
		$this->datatype = (string) $map['datatype'];
		$this->faceted = (boolean) $map['faceted'];
		$this->sortable = (boolean) $map['sortable'];
		$this->searchable = (boolean) $map['searchable'];
	}

}
?>