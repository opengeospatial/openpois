<?php

/**
 * Requires PHP5, php5-curl, SPL (for autoloading)
 */


//Oauth libs (from http://code.google.com/p/oauth-php/)
require_once('OAuthStore.php');
require_once('OAuthRequester.php');

/**
 * Represents the public Factual API. Supports running queries against Factual
 * and inspecting the response. Supports the same levels of authentication
 * supported by Factual's API.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class Factual {
	
  const DRIVER_HEADER_TAG = "factual-php-driver-v1.1.1"; //Custom headerPHP 
  private $factHome; //string assigned from config
  private $signer; //OAuthStore object
  private $config; //array from config.ini file on construct
  private $geocoder; //geocoder object (unsupported, experimental)
  private $configPath = "config.ini"; //where the config file is found: path + file
  private $lastTable = null; //last table queried

  /**
   * Constructor. Creates authenticated access to Factual.
   * @param string key your oauth key.
   * @param string secret your oauth secret.
   */
  public function __construct($key,$secret) {
  	//load configuration
  	$this->loadConfig();
    $this->factHome = $this->config['factual']['endpoint']; //assign endpoint
    //create authentication object
    $options = array('consumer_key' => $key, 'consumer_secret' => $secret);
	$this->signer = OAuthStore::instance("2Leg", $options );
	//register autoloader
	spl_autoload_register(array(get_class(), 'factualAutoload'));
  }

	/**
	 * Sets location of config file at runtime
	 * @param string path path+filename
	 * @return void
	 */
	protected function setConfigPath($path){
		$this->configPath = $path;
	}

 	/**
 	 * Loads config file from ini
 	 * @return void
 	 */
 	protected function loadConfig(){
 		if (!$this->config){
 			try{
 				$this->config = parse_ini_file($this->configPath,true);
 			} catch (Exception $e) {
 				throw new Exception ("Failed parsing config file");
 			}
 		}
 	}
 
  /**
   * Change the base URL at which to contact Factual's API. This
   * may be useful if you want to talk to a test or staging
   * server withou changing config
   * Example value: <tt>http://staging.api.v3.factual.com/t/</tt>
   * @param urlBase the base URL at which to contact Factual's API.
   * @return void
   */
  public function setFactHome($urlBase) {
    $this->factHome = $urlBase;
  }

  /**
   * Convenience method to return Crosswalks for the specific query.
   * @param string table Table name
   * @param object query Query Object
   */
  public function crosswalks($table, $query) {
    return $this->fetch($table, $query)->getCrosswalks();
  }

  /**
   * Factual Fetch Abstraction
   * @param string tableName The name of the table you wish to query (e.g., "places")
   * @param obj query The query to run against <tt>table</tt>.
   * @return object ReadResponse object with result of running <tt>query</tt> against Factual.
   */
  public function fetch($tableName, $query) {
  	switch (get_class($query)) {
	    case "FactualQuery":
	    	$res = new ReadResponse($this->request($this->urlForFetch($tableName, $query)));
	        break;
	    case "CrosswalkQuery":
	        $res = new CrosswalkResponse($this->request($this->urlForCrosswalk($tableName, $query)));
	        break;
	    case "ResolveQuery":
	        $res = new ResolveResponse($this->request($this->urlForResolve($tableName, $query))); 
	        break;
	    default:
	    	throw new Exception(__METHOD__." class type '".get_class($query)."' not recognized");
	    	$res = false;
	} 
	$this->lastTable = $tableName; //assign table name to object for logging
	return $res;   
  }
  
  /**
   * Resolves and returns resolved entity or null (shortcut method -- experimental)
   * @param string tableName Table name
   * @param array vars Attributes of entity to be matched in key=>value pairs
   * @return array | null
   */
	public function resolve($tableName,$vars){
		$query = new ResolveQuery();
		foreach ($vars as $key => $value){
			$query->add($key,$value);
		}
        $res = new ResolveResponse($this->request($this->urlForResolve($tableName, $query)));
        return $res->getResolved(); 		
	}

	/**
	 * @return object SchemaResponse object
	 */
  public function schema($tableName) {
    return new SchemaResponse($this->request($this->urlForSchema($tableName)));
  }

  private function urlForSchema($tableName) {
    return $this->factHome . "t/" . $tableName . "/schema";
  }

  private function urlForCrosswalk($tableName, $query) {
    return $this->factHome.$tableName."/crosswalk?".$query->toUrlQuery();
  }

  private function urlForResolve($tableName, $query) {
    return $this->factHome.$tableName."/resolve?".$query->toUrlQuery();
  }

  private function urlForFetch($tableName, $query) {
    return $this->factHome."t/".$tableName."?".$query->toUrlQuery();
  }

	/**
	 * Sign the request, perform a curl request and return the results
	 * @param string urlStr unsigned URL request
	 * @return array ex: array ('code'=>int, 'headers'=>array(), 'body'=>string)
	 */
  private function request($urlStr) {
	$requestMethod = "GET";
	$params = null;
	$customHeaders[CURLOPT_HTTPHEADER] = array("X-Factual-Lib: ".self::DRIVER_HEADER_TAG); //custom header
    // Build request with OAuth request params
    $request = new OAuthRequester($urlStr, $requestMethod, $params);
 	//Make request
    try {
    	$result = $request->doRequest(0,$customHeaders);
    	$result['request'] = $urlStr; //pass request string onto response
    	$result['tablename'] = $this->lastTable; //pass table name to result object
    	return $result;
	} catch(Exception $e) {
		$factualE = new FactualApiException($e);
		$factualE->requestMethod($requestMethod);	
		$factualE->requestUrl($urlStr);
		throw $factualE;
	}
  }
  
  //The following methods are included as handy convenience; unsupported and experimental
  //They rely on a loosely-coupled third-party service that can be easily swapped out
  
  	/**
	* Geocodes address string or placename
	* @param string q 
	* @return array
	*/
  public function geocode($address){
  	return $this->getGeocoder()->geocode($address);
  }
  	/**
	* Reverse geocodes long/lat to the smallest bounding WOEID
	* @param real long Decimal Longitude
	* @param real lat Decimal Latitude
	* @return array single result
	*/
  public function reverseGeocode($lon, $lat){
  	return $this->getGeocoder()->reversegeocode($lon, $lat);
  }
  
  	/**
	* Geocodes address string or placename
	* @param string q 
	* @return array
	*/
  private function getGeocoder(){
  	if (!$this->geocoder){
  		$this->geocoder = new GeocoderWrapper;
  	}
  	return $this->geocoder;
  }
  
  /**
   * Autoloader for file dependencies
   * Called by spl_autoload_register() to avoid conflicts with autoload() methods from other libs
   */
  public static function factualAutoload($className) {
  		include dirname(__FILE__)."/".$className . ".php";
  }
  
}


  
?>
