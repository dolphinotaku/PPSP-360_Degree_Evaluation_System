<?php
// require_once 'DatabaseManager.php';
// require_once 'SecurityManager.php';

class SimpleTableManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "department";
	//protected $securityManager;
    
    function __construct() {
		parent::__construct();
		//$this->securityManager = new SecurityManager();
    }
	function Initialize($tableName=""){
		$this->table = $tableName;
//		$this->debug = true;
        
		parent::Initialize();
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
	/*
    function insert(){
    	$this->ResetResponseArray();
    	$responseArray = $this->GetResponseArray();
		if($this->securityManager->CRUD_PremissionCheck(__FUNCTION__)){
		 	$responseArray = parent::{__FUNCTION__}();
		}
		return $responseArray;
	}

    function select(){
    	$responseArray;
		if($this->securityManager->CRUD_PremissionCheck(__FUNCTION__)){
		 	$responseArray = parent::{__FUNCTION__}();
		}
		return $responseArray;
	}

    function update(){
    	$responseArray;
		if($this->securityManager->CRUD_PremissionCheck(__FUNCTION__)){
		 	$responseArray = parent::{__FUNCTION__}();
		}
		return $responseArray;
	}

    function delete(){
    	$responseArray;
		if($this->securityManager->CRUD_PremissionCheck(__FUNCTION__)){
		 	$responseArray = parent::{__FUNCTION__}();
		}
		return $responseArray;
	}
	*/
}
?>
