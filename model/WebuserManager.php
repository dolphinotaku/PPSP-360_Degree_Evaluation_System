<?php
// require_once 'DatabaseManager.php';

class WebuserManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "webuser";
    
    function __construct() {
		parent::__construct();
        $this->Initialize();
    }
	function Initialize(){
		// set parent dataSchema
		parent::setDataSchemaForSet();
		// set construct _ index
		parent::setArrayIndex();
        
        $this->isSelectAllColumns = false;
        $this->selectWhichCols = "userID, loginID, status, isDisable, activeDate, premissionID";
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
	
    // function setColumn1($value) {
    //     $this->_['column1'] = $value;
    // }
    // function getColumn2() {
    //     return "this getColumn2()".$this->_['column2'];
    // }
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
}
?>
