<?php

class EvaProposalManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "evaproposal";
    
    function __construct() {
		parent::__construct();
		$this->debug = true;
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
    
    function CheckDuplicateEvaluateeor(){
        $backupRecord = $this->_;
        
        $this->Initialize();
        $this->EvaluationCode = $backupRecord["EvaluationCode"];
        $this->Evaluatee = $backupRecord["Evaluatee"];
        $this->Evaluator = $backupRecord["Evaluator"];
        
        $responseArray = $this->select();
        
		if($this->responseArray['num_rows'])
			$isKeyExists = true;
		else
			$isKeyExists = false;
        
        $this->_ = $backupRecord;

		return $isKeyExists;
    }
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
}
?>