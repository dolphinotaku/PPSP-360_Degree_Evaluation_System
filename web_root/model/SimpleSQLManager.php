<?php
// require_once 'DatabaseManager.php';

class SimpleSQLManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "";
	protected $sql = "";
	protected $printDataAsVertical = false;
    
    function __construct() {
		parent::__construct();
		$this->debug = true;
    }
	function Initialize(){
		// set parent dataSchema
		//parent::setDataSchemaForSet();
		// set construct _ index
		//parent::setArrayIndex();
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
	
	function Execute($sql_str = ""){
		if(!isset($sql_str) || $sql_str == "")
			$sql_str = $this->sql;
		if(!$this->printDataAsVertical)
			return $this->queryForDataArray($sql_str);
		else
			return $this->queryResultToArrayVertical($sql_str);
	}
}
?>
