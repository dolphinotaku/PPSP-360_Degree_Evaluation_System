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
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
	
	function SelectGeneralUserInfo(){
        $this->isSelectAllColumns = false;
        //$this->selectWhichCols = "UserID, LoginID, Status, isDisable, ActiveDate, PremissionID, AccountType";
        $this->selectWhichCols = "UserID, LoginID, Status, ActivateDate, AccountType";
		
		$responseArray = $this->select();
        $this->isSelectAllColumns = true;
		
		return $responseArray;
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
