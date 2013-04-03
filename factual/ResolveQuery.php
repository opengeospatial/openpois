<?php
/**
 * Represents a Factual Resolve query.
 * This is a refactoring of the Factual Driver by Aaron: https://github.com/Factual/factual-java-driver
 * @author Tyler
 */
class ResolveQuery extends FactualQuery {
  private $values = array();
  	/**
	 * Whether this lib must perform URL encoding.
	 * Set to avoid double or absent encoding
	 */
	const URLENCODE = true;

	/**
	 * Adds name/key pair to query for eventual resolution
	 * @param string key Attribute name
	 * @param mixed val Attribute value
	 * $return object This query object 
	 */
  public function add($key, $val) {
  	$this->values[$key]=$val;
    return $this;
  }

	/**
	 * @return string
	 */
  public function toUrlQuery() {
    return $this->urlPair("values", $this->toJsonStr($this->values));
  }

	/**
	 * @return string
	 */
  private function toJsonStr($var) {
    try {
      return json_encode($this->values);
    } catch (Exception $e) {
      throw new Exception($e);
    } 
  }

	private function urlPair($name, $val) {
		if ($val != null) {
			try {		
				if (self::URLENCODE){	
					return $name."=".urlencode($val);
				} else {
					return $name."=".$val;
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
