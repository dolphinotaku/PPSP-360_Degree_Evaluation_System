<?php

class StaffManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "staff";
    
    function __construct() {
		parent::__construct();
        // parent::Initialize();
    }
	// function Initialize(){
	// 	// set parent dataSchema
	// 	parent::setDataSchemaForSet();
	// 	// set construct _ index
	// 	parent::setArrayIndex();
 //        echo "rwe";
	// }
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
}
?>