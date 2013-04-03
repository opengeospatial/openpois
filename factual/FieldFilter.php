<?php

class FieldFilter implements FactualFilter {
	
  private $fieldName; //string
  private $op; //sting
  private $arg; //obj

  /**
   * Creates filters on specific field criteria
   * @param string op Operator
   * @param string fieldName Field Name
   * @param string object Argument 
   */
  public function FieldFilter($op, $fieldName, $arg) {
    $this->op = $op;
    $this->fieldName = $fieldName;
    $this->arg = $arg;
    return true;
  }

  /**
   * Produces JSON representation of the represented filter logic.  For example:
   * <pre>
   * {"first": {"$eq":"Jack"}}
   * {"first": {"$in":["a, b, c"]}}
   * </pre>
   * @return string
   */

  public function toJsonStr() {
    return "{\"" . $this->fieldName . "\":{\"" . $this->op . "\":" . json_encode($this->arg) . "}}";
  }
  

}

?>
