<?php


/**
 * Represents a single Place record from Factual.
 * @author Tyler
 * @package Factual
 * @license Apache 2.0
 */
class FactualPlace {
	private $factual_id = null; //string
	private $name = null; //string 
	private $po_box = null; //string
	private $address = null; //string
	private $address_extended = null; //string
	private $locality = null; //string
	private $region = null; //string
	private $admin_region = null; //string
	private $posttown = null; //string
	private $postcode = null; //string
	private $country = null; //string
	private $tel = null; //string
	private $fax = null; //string
	private $website = null; //string
	private $latitude = null; //string
	private $longitude = null; //string
	private $category = null; //string
	private $status = null; //bool
	private $email = null; //string  

	/**
	 * Constructor takes array of atributes for single crosswalk result
	 */
	public function __construct($attrs) {
		foreach ($attrs as $key => $value) {
			$this-> $key = $value;
		}
		//set status as boolean
		/*
		if ($this->status != null){
			$this->status = (boolean) $this->status;
		}
		*/
	}
	/**
	 * @return string
	 */
	public function getFactualId() {
		return $this->factual_id;
	}
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * @return string
	 */
	public function getPOBox() {
		return $this->po_box;
	}
	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}
	/**
	 * @return string
	 */
	public function getAddressExtended() {
		return $this->address_extended;
	}
	/**
	* @return string
	*/
	public function getLocality() {
		return $this->locality;
	}
	/**
	 * @return string
	 */
	public function getRegion() {
		return $this->region;
	}
	/**
	 * @return string
	 */
	public function getAdminRegion() {
		return $this->admin_region;
	}
	/**
	 * @return string
	 */
	public function getPostTown() {
		return $this->posttown;
	}
	/**
	 * @return string
	 */
	public function getPostcode() {
		return $this->postcode;
	}
	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}
	/**
	 * @return string
	 */
	public function getTel() {
		return $this->tel;
	}
	/**
	 * @return string
	 */
	public function getFax() {
		return $this->fax;
	}
	/**
	 * @return string
	 */
	public function getWebsite() {
		return $this->website;
	}
	/**
	 * @return string
	 */
	public function getLongitude() {
		return $this->longitude;
	}
	/**
	 * @return string
	 */
	public function getLatitude() {
		return $this->latitude;
	}
	/**
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}
	/**
	 * @return bool
	 */
	public function getStatus() {
		return $this->status;
	}
	/**
	 * @return bool
	 */
	public function getEmail() {
		return $this->email;
	}
	/**
	 * @return string
	 */
	public function toString() {
		$objectProps = get_object_vars($this);
		$objectProps = array_filter($objectProps);
		return json_encode($objectProps);
	}

	public function __get($param) {
		return $this-> $param;
	}

}
?>
