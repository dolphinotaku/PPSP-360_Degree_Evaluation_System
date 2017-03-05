<?php

class QuestionnaireManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "questionnaire";
    
    function __construct() {
		parent::__construct();
//		$this->debug = true;
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
}

class QuestionManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "question";
    
    function __construct() {
		parent::__construct();
//		$this->debug = true;
        // parent::Initialize();
    }
    
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
}

class QuestionnaireResultManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "questionnaireResult";
    
    function __construct() {
		parent::__construct();
//		$this->debug = true;
        // parent::Initialize();
    }
    
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
}

?>