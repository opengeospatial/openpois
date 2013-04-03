<?php
/**
 * Represents the basic concept of a response from Factual.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
abstract class FactualResponse {

  protected $objects = array(); 
  protected $version = null; //string
  protected $status = null; //string
  protected $totalRowCount = null; //int
  protected $includedRows = null; //int
  protected $data = array();
  protected $json;
  protected $tableName = null; //table getting queried
  protected $countTotal = null;
  protected $responseHeaders = array();
  protected $responseCode = null;
  protected $request = null;
  protected $tableTypes = array ( //lookup for table-to-object representation
		'places' => "FactualPlace"
  );

  /**
   * Constructor, parses return values from CURL in factual::request() 
   * @param array response The JSON response String returned by Factual.
   */
  public function __construct($apiResponse) {
    try {
    	$this->json = $apiResponse['body'];
    	$this->parseResponse($apiResponse);
    } catch (Exception $e) {
    	//add note about json encoding borking here
      throw $e ();
    }
  }

	/**
	 * Parses response from CURL
	 * @param array apiResponse response from curl
	 * @return void
	 */
	protected function parseResponse($apiResponse){
		$this->parseJSON($apiResponse['body']);
		$this->tableName = $apiResponse['tablename'];
		$this->responseHeaders = $apiResponse['headers'];
		$this->responseCode = $apiResponse['code'];
		$this->request = $apiResponse['request'];
	}

	/**
	 * Get response headers sent by Factual
	 * @return array
	 */
	public function getResponseHeaders(){
		return $this->responseHeaders;
	}

	/**
	 * Get HTTP response code
	 * @return int
	 */
	public function getResponseCode(){
		return $this->responseCode;
	}

	/**
	 * Gets table name call was made against
	 * @return string
	 */
	protected function getTableName(){
		return $this->tableName;
	}

	/**
	 * Parses JSON as array and assigns object values
	 * @param string json JSON returned from API
	 * @return array structured JSON
	 */
	protected function parseJSON($json){
		//assign data value
    	$rootJSON = json_decode($json,true);
    	$this->data = $rootJSON['response']['data'];
    	//assign status value
    	$this->status = $rootJSON['status'];
    	//assign version
    	$this->version = $rootJSON['version'];
    	//assign total row count
    	if(isset($rootJSON['response']['total_row_count'])){
    		$this->countTotal = $rootJSON['response']['total_row_count'];
    	}
    	if(isset($rootJSON['response']['included_rows'])){
    		$this->includedRows = $rootJSON['response']['included_rows'];
    	}    	
    	return $rootJSON;	
	}

  /**
   * Get the entire JSON response from Factual
   * @return string 
   */
  public function getJson() {
    return $this->json;
  }

  /**
   * Get the status returned by the Factual API server, e.g. "ok".
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get the version returned by the Factual API server, e.g. "3".
   * @return numeric
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Get count of all entities meeting query criteria, or null if unknown.
   * @return int | null
   */
  public function getTotalRowCount() {
    return $this->totalRowCount;
  }

  /**
   * Get count of result rows returned in this response.
   * @return int 
   */
  public function getIncludedRowCount() {
    return $this->includedRows;
  }

  /**
   * Get the returned entities as an array 
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Get the return entities as JSON 
   * @return the main data returned by Factual.
   */
  public function getDataAsJSON() {
    	return json_encode($this->data);
  }

  /**
   * Gets count of elements returned in this page of result set (not total count)
   * @return int 
   */
  public function size() {
	return $this->includedRows;  
  }

  /**
   * Get the first data record or, null if no data was returned.
   * @return array 
   */
  public function first() {
    if(empty($this->data)) {
      return null;
    } else {
      return $this->data[0];
    }
  }

  /**
   * Get total result count. Must be specifically requested via Query::includeRowCount()
   * @return int | null
   */
  public function getRowCount() {
    return $this->countTotal;
  }

  /**
   * Checks whether data was returned by Factual server.  True if Factual's 
   * response did not include any results records for the query, false otherwise.
   * @return bool
   */
  public function isEmpty() {
    return $this->includedRows == 0;
  }

  /**
   * Subclasses of FactualResponse must provide access to the original JSON
   * representation of Factual's response. Alias for getJson()
   * @return string
   */
  public function toString() {
    return $this->getJson();
  }
  
  /**
   * Get URL request string, does not include auth
   * @return string
   */
  public function getRequest(){
  	return $this->request;
  }
  
  /**
   * Get table name queried
   * @return string
   */
  public function getTable(){
  	return $this->tableName;
  }  
  
   /**
   * Get http headers returned by Factual
   * @return string
   */
  public function getHeaders(){
  	return $this->responseHeaders;
  }   
  
   /**
   * Get http status code returned by Factual
   * @return string
   */
  public function getCode(){
  	return $this->responseCode;
  }    
  
   /*
  * Results as array of objects
  * @param string type Entity type: FactualPlace, Crosswalk
  * @return array Array of objects

	public function getObjects($type){
		if (is_array($this->data)){
			foreach ($this->data as $entity){
				$this->objects[] = new $type($entity);
			}
			return $this->objects;
		} else {
			return array();
		}
	}
  */
}
?>

