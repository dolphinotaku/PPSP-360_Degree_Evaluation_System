<?php

class EvaluationManager extends DatabaseManager {
    protected $_ = array(
		// this Array structure By Initialize()
        // 'columnName1' => value,
        // 'columnName2' => value,
    );
	
	protected $table = "evaluation";
    
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
	
	public function SelectNotOccurredEvaluation($tempOffset = 0, $tempLimit = 10){
            
		$tempTotalOffset = $tempOffset;

		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete("select");

		$array = $this->_;
		$dataSchema = $this->dataSchema;
		$tempSelectWhichCols = "*";
		if(!$this->isSelectAllColumns)
			$tempSelectWhichCols = $this->selectWhichCols;
		
		$whereSQL = "";
		$isWhere = false;
		foreach ($array as $index => $value) {
			// if TableManager->value =null, ignore
			if(isset($value)){//$array[$index])){
				if(isset($this->SearchDataType($dataSchema['data'], 'Field', $index)[0]['Default']))
					if ($value == $this->SearchDataType($dataSchema['data'], 'Field', $index)[0]['Default'])
						continue;
				$whereSQL .= "`".$index."` = ". $value . " and ";
				$isWhere = true;
			}
		}
        $whereSQL = "`StartDate` > CURDATE()";
        $sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` where %s LIMIT %s OFFSET %s",
                $this->table,
                $whereSQL,
                $tempLimit,
                $tempTotalOffset);

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();
		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete("select");
		return $this->GetResponseArray();
	}
    
    public function SelectFinishedEvaluation($tempOffset = 0, $tempLimit = 10){
		$tempTotalOffset = $tempOffset;

		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete("select");

		$array = $this->_;
		$dataSchema = $this->dataSchema;
		$tempSelectWhichCols = "*";
		if(!$this->isSelectAllColumns)
			$tempSelectWhichCols = $this->selectWhichCols;
		
		$whereSQL = "";
		$isWhere = false;
		foreach ($array as $index => $value) {
			// if TableManager->value =null, ignore
			if(isset($value)){//$array[$index])){
				if(isset($this->SearchDataType($dataSchema['data'], 'Field', $index)[0]['Default']))
					if ($value == $this->SearchDataType($dataSchema['data'], 'Field', $index)[0]['Default'])
						continue;
				$whereSQL .= "`".$index."` = ". $value . " and ";
				$isWhere = true;
			}
		}
        $whereSQL = "`EndDate` < CURDATE()";
        $sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` where %s LIMIT %s OFFSET %s",
                $this->table,
                $whereSQL,
                $tempLimit,
                $tempTotalOffset);

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();
		$this->responseArray['table_schema'] = $this->getDataSchema()['data'];

		$this->afterCreateInsertUpdateDelete("select");
		return $this->GetResponseArray();
    }
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
}
?>