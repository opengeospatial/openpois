<?php
error_reporting(E_ERROR);
require_once ('Factual.php');

/**
 * Test methods for Factual API. Not for production use.
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class FactualTest {

	private $factual;
	private $writeToFile = null;
	private $testTables = array('global'=>"global",'resolve'=>"places",'crosswalk'=>"places",'schema'=>"places",'restaurants'=>"restaurants-us",'us'=>"places");
	private $classes = array (
		"FactualCircle",
		"FactualColumnSchema",
		"Crosswalk",
		"CrosswalkQuery",
		"FactualApiException",
		"FieldFilter",
		"FactualFilter",
		"FilterGroup",
		"GeocoderWrapper",
		"FactualPlace",
		"FactualQuery",
		"QueryBuilder",
		"ReadResponse",
		"ResolveQuery",
		"ResolveResponse",
		"FactualResponse",
		"SchemaResponse"
	);
	private $countries = array (
		//'AF' => 'Afghanistan',
		//'AL' => 'Albania',
		//'DZ' => 'Algeria',
		//'AS' => 'American Samoa',
		//'AD' => 'Andorra',
		//'AO' => 'Angola',
		//'AI' => 'Anguilla',
		//'AQ' => 'Antarctica',
		//'AG' => 'Antigua And Barbuda',
		'AR' => 'Argentina',
		//'AM' => 'Armenia',
		//'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		//'AZ' => 'Azerbaijan',
		//'BS' => 'Bahamas',
		//'BH' => 'Bahrain',
		//'BD' => 'Bangladesh',
		//'BB' => 'Barbados',
		//'BY' => 'Belarus',
		'BE' => 'Belgium',
		//'BZ' => 'Belize',
		//'BJ' => 'Benin',
		//'BM' => 'Bermuda',
		//'BT' => 'Bhutan',
		//'BO' => 'Bolivia',
		//'BA' => 'Bosnia And Herzegovina',
		//'BW' => 'Botswana',
		//'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		//'IO' => 'British Indian Ocean Territory',
		//'BN' => 'Brunei',
		//'BG' => 'Bulgaria',
		//'BF' => 'Burkina Faso',
		//'BI' => 'Burundi',
		//'KH' => 'Cambodia',
		//'CM' => 'Cameroon',
		'CA' => 'Canada',
		//'CV' => 'Cape Verde',
		//'KY' => 'Cayman Islands',
		//'CF' => 'Central African Republic',
		//'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		//'CX' => 'Christmas Island',
		//'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Columbia',
		//'KM' => 'Comoros',
		//'CG' => 'Congo',
		//'CK' => 'Cook Islands',
		//'CR' => 'Costa Rica',
		//'CI' => 'Cote D\'Ivorie (Ivory Coast)',
		'HR' => 'Croatia (Hrvatska)',
		//'CU' => 'Cuba',
		//'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		//'CD' => 'Democratic Republic Of Congo (Zaire)',
		'DK' => 'Denmark',
		//'DJ' => 'Djibouti',
		//'DM' => 'Dominica',
		//'DO' => 'Dominican Republic',
		//'TP' => 'East Timor',
		//'EC' => 'Ecuador',
		'EG' => 'Egypt',
		//'SV' => 'El Salvador',
		//'GQ' => 'Equatorial Guinea',
		//'ER' => 'Eritrea',
		//'EE' => 'Estonia',
		//'ET' => 'Ethiopia',
		//'FK' => 'Falkland Islands (Malvinas)',
		//'FO' => 'Faroe Islands',
		//'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		//'FX' => 'France, Metropolitan',
		//'GF' => 'French Guinea',
		//'PF' => 'French Polynesia',
		//'TF' => 'French Southern Territories',
		//'GA' => 'Gabon',
		//'GM' => 'Gambia',
		//'GE' => 'Georgia',
		'DE' => 'Germany',
		//'GH' => 'Ghana',
		//'GI' => 'Gibraltar',
		'GR' => 'Greece',
		//'GL' => 'Greenland',
		//'GD' => 'Grenada',
		//'GP' => 'Guadeloupe',
		//'GU' => 'Guam',
		//'GT' => 'Guatemala',
		//'GN' => 'Guinea',
		//'GW' => 'Guinea-Bissau',
		//'GY' => 'Guyana',
		//'HT' => 'Haiti',
		//'HM' => 'Heard And McDonald Islands',
		//'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		//'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		//'IR' => 'Iran',
		//'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IT' => 'Italy',
		//'JM' => 'Jamaica',
		'JP' => 'Japan',
		//'JO' => 'Jordan',
		//'KZ' => 'Kazakhstan',
		//'KE' => 'Kenya',
		//'KI' => 'Kiribati',
		//'KW' => 'Kuwait',
		//'KG' => 'Kyrgyzstan',
		//'LA' => 'Laos',
		//'LV' => 'Latvia',
		//'LB' => 'Lebanon',
		//'LS' => 'Lesotho',
		//'LR' => 'Liberia',
		//'LY' => 'Libya',
		//'LI' => 'Liechtenstein',
		//'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		//'MO' => 'Macau',
		//'MK' => 'Macedonia',
		//'MG' => 'Madagascar',
		//'MW' => 'Malawi',
		'MY' => 'Malaysia',
		//'MV' => 'Maldives',
		//'ML' => 'Mali',
		//'MT' => 'Malta',
		//'MH' => 'Marshall Islands',
		//'MQ' => 'Martinique',
		//'MR' => 'Mauritania',
		//'MU' => 'Mauritius',
		//'YT' => 'Mayotte',
		'MX' => 'Mexico',
		//'FM' => 'Micronesia',
		//'MD' => 'Moldova',
		//'MC' => 'Monaco',
		//'MN' => 'Mongolia',
		//'MS' => 'Montserrat',
		//'MA' => 'Morocco',
		//'MZ' => 'Mozambique',
		//'MM' => 'Myanmar (Burma)',
		//'NA' => 'Namibia',
		//'NR' => 'Nauru',
		//'NP' => 'Nepal',
		'NL' => 'Netherlands',
		//'AN' => 'Netherlands Antilles',
		//'NC' => 'New Caledonia',
		//'NZ' => 'New Zealand',
		//'NI' => 'Nicaragua',
		//'NE' => 'Niger',
		//'NG' => 'Nigeria',
		//'NU' => 'Niue',
		//'NF' => 'Norfolk Island',
		//'KP' => 'North Korea',
		//'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		//'OM' => 'Oman',
		//'PK' => 'Pakistan',
		//'PW' => 'Palau',
		//'PA' => 'Panama',
		//'PG' => 'Papua New Guinea',
		//'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		//'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		//'QA' => 'Qatar',
		//'RE' => 'Reunion',
		//'RO' => 'Romania',
		'RU' => 'Russia',
		//'RW' => 'Rwanda',
		//'SH' => 'Saint Helena',
		//'KN' => 'Saint Kitts And Nevis',
		//'LC' => 'Saint Lucia',
		//'PM' => 'Saint Pierre And Miquelon',
		//'VC' => 'Saint Vincent And The Grenadines',
		//'SM' => 'San Marino',
		//'ST' => 'Sao Tome And Principe',
		//'SA' => 'Saudi Arabia',
		//'SN' => 'Senegal',
		//'SC' => 'Seychelles',
		//'SL' => 'Sierra Leone',
		//'SG' => 'Singapore',
		//'SK' => 'Slovak Republic',
		//'SI' => 'Slovenia',
		//'SB' => 'Solomon Islands',
		//'SO' => 'Somalia',
		'SG' => 'Singapore',
		'ZA' => 'South Africa',
		//'GS' => 'South Georgia And South Sandwich Islands',
		'KR' => 'South Korea',
		'ES' => 'Spain',
		//'LK' => 'Sri Lanka',
		//'SD' => 'Sudan',
		//'SR' => 'Suriname',
		//'SJ' => 'Svalbard And Jan Mayen',
		//'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		//'SY' => 'Syria',
		'TW' => 'Taiwan',
		//'TJ' => 'Tajikistan',
		//'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		//'TG' => 'Togo',
		//'TK' => 'Tokelau',
		//'TO' => 'Tonga',
		//'TT' => 'Trinidad And Tobago',
		//'TN' => 'Tunisia',
		'TR' => 'Turkey',
		//'TM' => 'Turkmenistan',
		//'TC' => 'Turks And Caicos Islands',
		//'TV' => 'Tuvalu',
		//'UG' => 'Uganda',
		//'UA' => 'Ukraine',
		//'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		//'UM' => 'United States Minor Outlying Islands',
		//'UY' => 'Uruguay',
		//'UZ' => 'Uzbekistan',
		//'VU' => 'Vanuatu',
		//'VA' => 'Vatican City (Holy See)',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		//'VG' => 'Virgin Islands (British)',
		//'VI' => 'Virgin Islands (US)',
		//'WF' => 'Wallis And Futuna Islands',
		//'EH' => 'Western Sahara',
		//'WS' => 'Western Samoa',
		//'YE' => 'Yemen',
		//'YU' => 'Yugoslavia',
		//'ZM' => 'Zambia',
		//'ZW' => 'Zimbabwe'
	);

	/**
	 * Primary test function. 
	 */
	public function test() {

		if (!$this->writeToFile) {
			echo "\n\nTesting Factual\n";
			echo "========================\n";
		} else {
			if ($this->writeToFile) {
				//remove extant log file
				@unlink($this->writeToFile);
			}
		}
		$this->testVersion();
		$this->classConflicts();
		$this->testExt();
		$this->testEncoding();
		$this->testConnect();
		$this->testQueryFilterLimitSort();
		$this->testUnicode();
		$this->testPunctuation();
		$this->testInCriterion();
		$this->testMultiFilter();
		$this->testGeoSearch();
		$this->testMultiCountry();
		$this->testResponseMetadata();
		$this->testGeocode();
		$this->testReverseGeocode();
		$this->testResolve();
		$this->testCrosswalk();
		$this->testSchema();
		$this->testCountries();
		
		if (!$this->writeToFile) {
			echo "========================\n";
		}
	}

	/**
	 * Set file to log report to. Echoes to screen by default
	 * @return void
	 */	
	public function setLogFile($fileName = null) {
		if ($fileName){
			$this->writeToFile = $fileName;
		}
	}

	private function testEncoding(){
		$requestSample = 10;
		$query = new FactualQuery;
		$query->field("category")->equal("Food & Beverage > Restaurants");
		$query->limit($requestSample);
		$res = $this->factual->fetch($this->testTables['global'], $query);
		if ($res->size() == $requestSample){
			$this->msg("HTML Encoding", true);
		} else {
			$this->msg("HTML Encoding", false);
		}
	}

	private function testResponseMetadata(){
		$requestSample = 10;
	 	$query = new FactualQuery;
		$query->search("Sushi");
		$query->limit($requestSample);
		$query->includeRowCount();
	    $res = $this->factual->fetch($this->testTables['global'], $query);
		
		// Get URL request string
		if (strlen($res->getRequest()) > 5){
			$this->msg("Response URL", true);
		} else {
			$this->msg("Response URL", false);
		}
	
		// Get the table name queried
		if ($res->getTable() == $this->testTables['global']){
			$this->msg("Request Table Name", true);
		} else {
			$this->msg("Request Table Name", false);
		}
		
		// Get http headers returned by Factual
		if (count($res->getHeaders()) > 2){
			$this->msg("Response Headers", true);
		} else {
			$this->msg("Response Headers", false);
		}
	
		// Get http status code returned by Factual	
		if ($res->getCode() > 0){
			$this->msg("Response Code", true);
		} else {
			$this->msg("Response Code", false);
		}	
		
		// Get total row count returned by Factual	
		if ($res->getRowCount() > 0){
			$this->msg("Total Row Count", true);
		} else {
			$this->msg("Total Row Count", false);
		}			
		
	}


	private function testUnicode(){
	 	$requestSample = 10;
	 	$query = new FactualQuery;
		$query->field("locality")->equal("大阪市");
		$query->limit($requestSample);
	    $res = $this->factual->fetch($this->testTables['global'], $query);
		if ($res->size() !== $requestSample){
			$this->msg("Unicode", false);
		} else {
			$this->msg("Unicode", true);
		}
	}

	private function testPunctuation(){
	 	$requestSample = 1;
	 	$query = new FactualQuery;
		$query->search("McDonald's, Santa Monica");
		$query->limit($requestSample);
	    $res = $this->factual->fetch($this->testTables['us'], $query);
		if ($res->size() !== $requestSample){
			$this->msg("Punctuation", false);
		} else {
			$this->msg("Punctuation", true);
		}
	}

	private function testInCriterion(){
	 	$requestSample = 10;
	 	$query = new FactualQuery;
		$query->search("Sushi");
		try {
		$query->field("locality")->in(array("Santa Monica","Los Angeles,Culver City"));
		$query->limit($requestSample);
		    $res = $this->factual->fetch($this->testTables['restaurants'], $query);		
		} catch (Exception $e) {
			$this->msg("'In' Filter", false, $e->getMessage());
			return false;
		}
		if ($res->size() !== $requestSample){
			$this->msg("'In' Filter", false);
		} else {
			$this->msg("'In' Filter", true);
		}
	}

	private function testMultiCountry(){
	 	$requestSample = 10;
	 	$query = new FactualQuery;
		$query->search("Sushi");
		$query->field("country")->in(array("US","CA"));
		$query->limit($requestSample);
	    $res = $this->factual->fetch($this->testTables['global'], $query);
		if ($res->size() !== $requestSample){
			$this->msg("Multi Country Search", false);
		} else {
			$this->msg("Multi Country Search", true);
		}
	}


	private function testGeocode(){
		$res = $this->factual->geocode("425 Sherman Ave, Palo Alto, CA, USA");
		if ($res['latitude'] == 37.425674 && $res['longitude'] == -122.143895){
			$this->msg("Geocoder", true);
		} else {
			$this->msg("Geocoder", false);
		}
	}

	private function testReverseGeocode(){
		$lon = -122.143895;
		$lat = 37.425674;
		$res = $this->factual->reverseGeocode($lon,$lat);
		if ($res['house'] == 425 && $res['street'] == "Sherman Ave"){
			$this->msg("Reverse Geocoder", true);
		} else {
			$this->msg("Reverse Geocoder", false);
		}
	}

/*
	private function testPlaceObjects(){
		$query = new FactualQuery();
		$query->limit(1);
		$res = $this->factual->fetch($this->testTables['global'], $query); 
		$places = $res->
	}
*/
	private function testGeoSearch(){
		$requestSample = 3;
		$query = new FactualQuery();
		$query->within(new FactualCircle(34.06018, -118.41835, 5000));
		$query->limit($requestSample); //only get ten results
		$query->sortAsc("\$distance"); //order results by distance
		$res = $this->factual->fetch($this->testTables['global'], $query);
		if ($res->size() !== $requestSample){
			$this->msg("Geo Search", false);
		} else {
			$this->msg("Geo Search", true);
		}
	}

	private function testCountries(){
		$requestSample = 3;
		foreach ($this->countries as $key => $value){
			$query = new FactualQuery();
			$query->field("country")->equal($key);
			$query->limit($requestSample);
			$res = $this->factual->fetch($this->testTables['global'], $query);
			if ($res->size() !== $requestSample){
				$this->msg("Checking ".$value, false);
				
				print_r(urldecode($res->getRequest()));
				exit;
				
			} else {
				$this->msg("Checking ".$value, true);
			}
		}
	}
	
	private function testMultiFilter(){
		$query = new FactualQuery;	
	 $query->_and(array(
       	$query->field("name")->equal("Starbucks"),
  	   $query->field("region")->equal("CA"),
  	   $query->field("country")->equal("US")
  	   )
	);
	$query->limit(1);
	$res = $this->factual->fetch($this->testTables['global'], $query);
	$record = $res->getData();
	$record = $record[0];
	if ($record['name'] == "Starbucks" && $record['region'] == "CA" && $record['country'] == "US") {
			$this->msg("Multi Filter", true);
		} else {
			$this->msg("Multi Filter", false);
		}
	}

	private function testSchema() {
		$res = $this->factual->schema($this->testTables['schema']);
		if ($res->getStatus() == "ok") {
			$this->msg("Schema Endpoint", true);
		} else {
			$this->msg("Schema Endpoint", false);
		}
	}

	private function testCrosswalk() {
		$query = $this->getQueryObject();
		$query = new CrosswalkQuery();
		$query->_namespace("foursquare");
		$query->namespaceId("4ae4df6df964a520019f21e3");
		$res = $this->factual->fetch($this->testTables['crosswalk'], $query);
		if ($res->getStatus() == "ok") {
			$this->msg("Crosswalk Endpoint", true);
		} else {
			$this->msg("Crosswalk Endpoint", false);
		}
	}

	private function testResolve() {
		$query = $this->getQueryObject();
		$query = new ResolveQuery();
		$query->add("name", "Buena Vista Cigar Club");
		$query->add("latitude", 34.06);
		$query->add("longitude", -118.40);
		$res = $this->factual->fetch($this->testTables['resolve'], $query);
		if ($res->getStatus() == "ok") {
			$this->msg("Resolve Endpoint", true);
		} else {
			$this->msg("Resolve Endpoint", false);
		}
	}

	private function testQueryFilterLimitSort() {
		$limit = 10;
		$query = new FactualQuery;
		$query->limit($limit);
		$query->sortAsc("name");
		$query->field("region")->equal("CA");
		$res = $this->factual->fetch($this->testTables['global'], $query);
		if ($res->getStatus() == "ok" && $res->getIncludedRowCount() == $limit) {
			$this->msg("Limit/Filter/Sort", true);
		} else {
			$this->msg("Limit/Filter/Sort", false);
		}
	}

	public function __construct($key, $secret) {
		if (!$key || !$secret){
			$this->msg("Key and secret are required in class constructor",null);
			exit;
		}
		$this->factual = new factual($key, $secret);
	}

	/**
	 * Runs a quick query to test key/secret
	 */
	private function testConnect() {
		if ($query = $this->getQueryObject()) {
			$this->setQueryObject($query);
			try {
				$res = $this->factual->fetch($this->testTables['global'], $query); //test query
			} catch (FactualAPIException $e) {
				$this->msg($e->getMessage(), "");
				$this->msg($e->getErrorType(), "");
				$this->msg($e->getCode(), "");
				$this->msg("API Exception", false);
			}
			$this->msg("API Connection", true);
		} else {
			$this->msg("Exiting", "");
			exit;
		}
	}

	/**
	 * Determines whether pre-existing classes with same name will conflict
	 * Only run when no others are loaded, of course
	 */
	private function classConflicts() {
		$extantClasses = array_flip(get_declared_classes()); //case sensitive
		foreach ($this->classes as $className) {
			if ($extantClasses[$className]) {
				$this->msg("Classname conflict exists: " . $className, false);
				$failures = true;
			}
		}
		if (!$failures) {
			$this->msg("No classname conflicts", true);
		} else {
			$this->msg("These conflicts must be resolved before this driver can be employed\n", "");
			exit;
		}
	}

	private function setQueryObject($query) {
		if ($query->search("pizza")) {
			$this->msg("Setting query parameter", true);
		} else {
			$this->msg("Setting query parameter", false);
		}
	}

	private function getQueryObject() {
		$query = new FactualQuery();
		if ($query) {
			$this->msg("Creating Query object", true);
			return $query;
		} else {
			$this->msg("Creating Query object", false);
		}
	}

	/**
	 * Confirms correct extensions (dependencies) are installed
	 */
	private function testExt() {
		$modules = array (
			"SPL",
			"curl"
		);
		$ext = array_flip(get_loaded_extensions());
		foreach ($modules as $module) {
			if ($ext[$module]) {
				$this->msg($module . " is loaded", true);
			} else {
				$this->msg($module . " is not loaded", false);
			}
		}
	}

	private function testVersion() {
		$version = explode('.', phpversion());
		if ((int) $version[0] >= 5) {
			$status = true;
		} else {
			$status = false;
		}
		$this->msg("PHP verison v5+", $status);
	}

	private function msg($mesage, $status, $deets=null) {
		$lineLength = 40;
		if (is_bool($status)) {
			//convert to string
			if ($status) {
				$status = "Pass";
			} else {
				$status = "Fail";
			}
			//color for cli
			if (!$this->writeToFile) {
				if ($status == "Pass") {
					$status = "\033[0;32m" . $status . "\033[0m";
				} else {
					$status = "\033[0;31m" . $status . "\033[0m";
				}
			}
		}
		//fancypants alignment
		$message = $mesage . str_repeat(" ", $lineLength -strlen($mesage)) . $status;
		if ($deets){$message .= "\t".$deets;}
		$message .= "\n";
		if ($this->writeToFile) {
			$fp = fopen($this->writeToFile, 'a');
			fwrite($fp,$message);
			fclose($fp);
		} else {
			echo $message;
		}
	}

}
?>
