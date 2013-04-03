<?php


/**
 * Represents a top level Factual query. Knows how to represent the query as URL
 * encoded key value pairs, ready for the query string in a GET request. (See
 * {@link #toUrlQuery()})
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */

class FactualQuery {
	private $fullTextSearch; //string
	private $selectFields = null; //otherwise comma-delineated list of fieldnames
	private $limit; //int
	private $offset; //int
	private $includeRowCount = false; //bool
	private $circle = null; //need to create this

	/**
	 * Whether this lib must perform URL encoding.
	 * Set to avoid double or absent encoding
	 */
	const URLENCODE = true;

	/**
	 * Holds all row filters for this Query. Implicit top-level AND.
	 */
	private $rowFilters = array ();

	/**
	 * Holds all results sorts for this Query. Example contents:
	 * <tt>"$distance:desc","name:asc","locality:asc"</tt>
	 */
	private $sorts = array ();

	/**
	 * Sets a full text search query. Factual will use this value to perform a
	 * full text search against various attributes of the underlying table, such
	 * as entity name, address, etc.
	 * 
	 * @param string term The text for which to perform a full text search.
	 * @return obj Query
	 */
	public function search($term) {
		$this->fullTextSearch = $term;
		return $this;
	}

	/**
	 * Sets the maximum amount of records to return from this Query.
	 * @param int limit The maximum count of records to return from this Query.
	 * @return this Query
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Sets the fields to select. This is optional; default behaviour is generally
	 * to select all fields in the schema.
	 * 
	 * @param mixed fields Fields to select as comma-delineated string or array
	 * @return this Query
	 */
	public function only($fields) {
		if (is_array($fields)) {
			$fields = implode(",", $fields);
		}
		$this->selectFields = $fields;
	}

	/**
	 * @return array of select fields set by only(), null if none.
	 */
	public function getSelectFields() {
		return $this->selectFields;
	}

	/**
	 * Sets this Query to sort field in ascending order.
	 * @param string field The field name to sort in ascending order.
	 * @return obj this Query
	 */
	public function sortAsc($field) {
		$this->sorts[] = $field . ":asc";
		return $this;
	}

	/**
	 * Sets this Query to sort field in descending order.
	 * @param string field The field to sort in descending order.
	 * @return this Query
	 */
	public function sortDesc($field) {
		$this->sorts[] = $field . ":desc";
		return $this;
	}

	/**
	 * Sets how many records in to start getting results (i.e., the page offset) for this Query.
	 * @param int offset The page offset for this Query.
	 * @return obj this Query
	 */
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * The response will include a count of the total number of rows in the table
	 * that conform to the request based on included filters. There is a performance hit. 
	 * The default behavior is to NOT include a row count.
	 * @return this Query, marked to return total row count when run.
	 */
	public function includeRowCount() {
		return $this->includeRowCount = true;
	}

	/**
	 * When true, the response will include a count of the total number of rows in
	 * the table that conform to the request based on included filters.
	 * Requesting the row count will increase the time required to return a
	 * response. The default behavior is to NOT include a row count. 
	 * @param includeRowCount
	 *          true if you want the results to include a count of the total
	 *          number of rows in the table that conform to the request based on
	 *          included filters.
	 * @return this Query.
	 * @internal changed method name. Unsure why we return obj here but not above
	 */
	public function setIncludeRowCount(boolean $includeRowCount) {
		$this->includeRowCount = $includeRowCount;
		return $this;
	}

	/**
	 * Begins construction of a new row filter for this Query.
	 * @param string field The name of the field on which to filter.
	 * @return obj QueryBuilder A partial representation of the new row filter.
	 */
	public function field($field) {
		return new QueryBuilder($this, $field);
	}

	/**
	 * Adds a filter so that results can only be (roughly) within the specified
	 * geographic circle.
	 * 
	 * @param circle The circle within which to bound the results.
	 * @return this Query.
	 */
	public function within($circle) {
		$this->circle = $circle;
		return $this;

	}

	/**
	 * Used to nest AND'ed predicates.
	 * @param array queries An array of query actions
	 * @internal method renamed from Java driver due to 'and' reserved word 
	 */
	public function _and($queries) {
		return $this->popFilters("\$and", $queries);
	}

	/**
	 * Used to nest OR'ed predicates.
	 * @param mixed queries A single query object or array thereof
	 * @internal method renamed from Java driver due to 'or' reserved word 
	 */
	public function _or($queries) {
		return $this->popFilters("\$or", $queries);
	}

	/**
	 * Adds <tt>filter</tt> object to this Query.
	 * @return void
	 */
	public function add($filter) {
		$this->rowFilters[] = $filter;
	}

	/**
	 * Builds and returns the query string to represent this Query when talking to
	 * Factual's API. Provides proper URL encoding and escaping.
	 * <p>
	 * Example output:
	 * <pre>
	 * filters=%7B%22%24and%22%3A%5B%7B%22region%22%3A%7B%22%24in%22%3A%22MA%2CVT%2CNH%22%7D%7D%2C%7B%22%24or%22%3A%5B%7B%22first_name%22%3A%7B%22%24eq%22%3A%22Chun%22%7D%7D%2C%7B%22last_name%22%3A%7B%22%24eq%22%3A%22Kok%22%7D%7D%5D%7D%5D%7D
	 * </pre>
	 * <p>
	 * (After decoding, the above example would be used by the server as:)
	 * <pre>
	 * filters={"$and":[{"region":{"$in":"MA,VT,NH"}},{"$or":[{"first_name":{"$eq":"Chun"}},{"last_name":{"$eq":"Kok"}}]}]}
	 * </pre>
	 * @return string The query string to represent this Query when talking to Factual's API.
	 * @internal re-activate geobounds method
	 */
	public function toUrlQuery() {

		$temp['select'] = $this->fieldsJsonOrNull();
		$temp['q'] = $this->fullTextSearch;
		$temp['sort'] = $this->sortsJsonOrNull();
		$temp['limit'] = ($this->limit > 0 ? $this->limit : null);
		$temp['offset'] = ($this->offset > 0 ? $this->offset : null);
		$temp['include_count'] =  ($this->includeRowCount ? "true" : null);
		$temp['filters'] = $this->rowFiltersJsonOrNull();
		$temp['geo'] = $this->geoBoundsJsonOrNull();
		$temp = array_filter($temp); //remove nulls		

		//encode (cannot use http_build_query() as we need to *raw* encode adn this not provided until v5.4)
		foreach ($temp as $key => $value){
			$temp2[] = $key."=".rawurlencode($value);		
		}	
		return implode("&", $temp2);
	}

	public function toString() {
		try {
			return urldecode($this->toUrlQuery());
		} catch (Exception $e) {
			throw $e;
		}
	}

	private function fieldsJsonOrNull() {
		if ($this->selectFields != null) {
			return $this->selectFields;
		} else {
			return null;
		}
	}

	private function sortsJsonOrNull() {
		if (!empty ($this->sorts)) {
			return implode(",", $this->sorts);
		} else {
			return null;
		}
	}

	private function geoBoundsJsonOrNull() {
		if ($this->circle != null) {
			return $this->circle->toJsonStr();
		} else {
			return null;
		}
	}

	private function rowFiltersJsonOrNull() {
		if (empty ($this->rowFilters)) {
			return null;
		} else
			if (count($this->rowFilters) === 1) {
				return $this->rowFilters[0]->toJsonStr();
			} else {
				$filterGroup = new FilterGroup($this->rowFilters);
				return $filterGroup->toJsonStr();
			}
	}

	/**
	 * Pops the newest Filter from each of <tt>queries</tt>,
	 * grouping each popped Filter into one new FilterGroup.
	 * Adds that new FilterGroup as the newest Filter in this
	 * Query.
	 * <p>
	 * The FilterGroup's logic will be determined by <tt>op</tt>.
	 * @param string op operator name
	 * @param array Array of Query filter criteria
	 * @return obj queries Query object
	 */
	private function popFilters($op, array $queries) {
		$group = new FilterGroup();
		$group->op($op);
		foreach ($queries as $query) {
			$group->add(array_pop($query->rowFilters));
		}
		$this->add($group);
		return $this;
	}

}
?>
