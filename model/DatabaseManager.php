<?php
/**
 *
 * @author KeithPoon <keith200620062006@yahoo.com.hk>
 * @version 3.5
 */
//require_once dirname(__FILE__).'/../globalVariable.php';
// require_once dirname(__FILE__).'/config.php';
require_once 'Core.php';

class DatabaseManager{
	private $hostname_fyp;
    private $database_fyp;
    private $username_fyp;
    private $password_fyp;

    protected $dbc;
    protected $sql_str;
    protected $previousInsertID;
    protected $i;
    
	// define by the getTheDataSchemaForSet;
	protected $showColumnList;
	protected $dataSchema;
	protected $dataSchemaCSharp;
	protected $tableIndex;
	protected $tablePrimaryKey;
	
	protected $debug = true;
	protected $hideSQL = false;
	protected $isTableSchemaFound = false;
	protected $isAllowSetDefaultValue = true;
	
	protected $isDefaultValueSet = false;
	// dataType for data transform in GetSQLValueString
	protected $text = "text";
	protected $int = "int";
	protected $long = "long";
	protected $double = "double";
	protected $date = "date";
	
	private $responseArray = array("data"=>null, "sql"=>null);
	private $responseArray_errs = array();

	// config the select mechanism.
	protected $isSelectAllColumns = true;
	protected $selectWhichCols = "*";
	public $pageNum = 1;
	protected $selectStep = 10;

	// update mechanism
	protected $ignoreTheLastDateCheck = false;

	// config is enable SecurityManager
	/**
	 * enable SecurityModule
	 * you must login to insert / update / delete any records.
	 *
	 * enable advance security check
	 * the select / insert / update / delete right are according to the database permission granted
	 * controller name
	**/
	protected $enableSecurityModule = false;
	protected $enableAdvanceSecurityCheck = false;
	protected $controllerName = "";
	protected $functionName = "";
	protected $functionDescription = "";

	// init the var, other tableManager should implete for CRUD permission checking
	protected $permsCtrlName;
	protected $permsFunctionName;
	protected $topRightToken = false;

	function GetRealEscapeString($value){
		if(!$this->dbc->connect_errno)
			return $this->dbc->real_escape_string($value);
		else
			return "have not connected to database.";
	}

	function GetResponseArray(){
		$this->responseArray["sql"] = $this->sql_str;
		$this->responseArray["error"] = join("\r\n", $this->responseArray_errs);

		if($this->hideSQL)
			$this->responseArray["sql"] = "SQL Masked****";
		
		return $this->responseArray;
	}
    
	function ResetResponseArray(){
		$this->responseArray = array();
		$this->responseArray_errs = array();

		$this->responseArray = $this->CreateResponseArray();
	}
    
	function CreateResponseArray(){
		$responseArray = array();
		$responseArray_errs = array();
		
		$arrayIndex = array("data", 
			"table_schema",
			"sql", 
			"num_rows", 
			"insert_id", 
			"affected_rows", 
			"access_status", 
			"process_result",
			"error");
		foreach ($arrayIndex as $indexValue){
			$responseArray[$indexValue] = null;
		}
		$responseArray["data"] = [];
		$responseArray["sql"] = null;
		$responseArray["access_status"] = Core::$access_status["None"];
		return $responseArray;
	}

	function SetDBContector($dbc){
		$this->dbc = $dbc;
	}

	function GetDBContector(){
		return $this->dbc;
	}
	
	/**
	 * Magic Methods: __construct(), Classes which have a constructor method call this method on each newly-created object,
	 * so it is suitable for any initialization that the object may need before it is used.
	 *
	 */
    function __construct(){
    	try {
    		$this->hostname_fyp = _DB_HOST;
    		$this->database_fyp = _DB_NAME;
    		$this->username_fyp = _DB_USER;
    		$this->password_fyp = _DB_PASS;

    		$hostname_fyp = $this->hostname_fyp;
    		$database_fyp = $this->database_fyp;
    		$username_fyp = $this->username_fyp;
    		$password_fyp = $this->password_fyp;
			
    		$this->dbc = new mysqli($hostname_fyp, $username_fyp, $password_fyp, $database_fyp) or trigger_error(mysqli_error(), E_USER_ERROR);
         	mysqli_set_charset($this->dbc, "utf8");
        }catch (Exception $e ) {
         	echo "Service unavailable";
         	echo "message: " . $e->message;   // not in live code obviously...
        	exit;
        }

		/*
			$_SERVER['PHP_SELF'] vs __FILE__

			__FILE__ in manager.php file
			when controll.php call manager.php file, __FILE__ will be come file_path/manager.php

			$_SERVER['PHP_SELF'] in manager.php file
			when controll.php call manager.php file, __FILE__ will be come file_path/controll.php						
		*/
		$this->permsCtrlName = basename($_SERVER['PHP_SELF']);
        
		$this->ResetResponseArray();
		$this->Initialize();
    }

    function Initialize(){
		$this->dataSchema = array();
		$this->dataSchemaCSharp = array();
		$this->_ = array();

		// set parent dataSchema
		$this->setDataSchemaForSet();
		$this->setArrayIndex();
    }

    function beginTransaction() {
        $this->dbc->autocommit(FALSE);
    }

    function commit() {
        $this->dbc->commit();
        $this->dbc->autocommit(TRUE);
    }

    function rollback() {
        $this->dbc->rollback();
        $this->dbc->autocommit(TRUE);
    }
    
    /**
     * execute sql query
     * 
     * @param string $sql_str, sql statemnet
     * @return mysqli_result, you may fetch array or read it own variable
     * //http://www.php.net/manual/en/class.mysqli-result.php
     * 
     */
    private function query($sql_str) {
    	//sleep(1); // for testing
        // echo $sql_str;
        $result = $this->dbc->query($sql_str);
        return $result;
    }
	
    // Deprecated, can be remove on the next time you see
//	function selectFirstRowFirstElement($sql_str, $isDebug=false) {
//		$result = $this->dbc->query($sql_str);
//		$row = $result->fetch_array(MYSQLI_NUM);
//		//	echo $row[0]."hellp";
//		//ec
//		if($isDebug===true){
//			echo "<br>";
//			$this->debug($sql_str);  //debug
//			echo "<br>";
//			echo "result: ".var_dump($result);
//			echo "<br>";
//			echo "elelment".$row[0]."elelment";
//		}
//		if($row[0])
//			return $row[0];
//		else
//			return false;
//		/*
//		if($result->num_rows)
//		{
//			$row = $result->fetch_array(MYSQLI_BOTH);
//			echo $row[0]
//			return $result->num_rows;
//		}
//		*/
//        if($result===false){
//        	return $this->debug($sql_str)."<br><br>".$result."<br><br><pre>".print_r($this->dbc->error_list)."</pre>";
//        }
//        return $result;
//	}

	/**
	 * call query() to execute sql query,
	 * assign the result to the instance's responseArray object,
	 * return the responseArray
	 *
	 * @param string $sql_str, sql statement
	 * @return Associative arrays, ['data'] = sql result, ['sql'] = sql statement, ['num_rows'] =  number of rows in a result, ['error'] = sql error
	 * 
	 * //http://www.php.net/manual/en/book.mysqli.php
	 * //http://www.php.net/manual/en/class.mysqli-result.php
	 *
	 */
    function queryForDataArray($fetchType = null) {
    	if(null == $fetchType)
    		$fetchType = MYSQLI_NUM;

    	$responseArray = Core::CreateResponseArray();
    	$sql_str = $this->sql_str;

		if($this->IsNullOrEmptyString($sql_str)){
			$sql_str = $this->sql_str;

			if($this->IsNullOrEmptyString($sql_str)){
				array_push($this->responseArray_errs, Core::$sys_err_msg["SQLNullOrEmpty"]);
				return $this->GetResponseArray();
			}
		}
        $result = $this->query($sql_str);

		// Fixed: num_rows only work for select, num_rows maybe null
		if(isset($result->num_rows))
			$responseArray['num_rows'] = $result->num_rows;
		else
			$responseArray['num_rows'] = 0;
		// insert_id return the lasted AUTO_INCREMENT field value.
		// Returns zero if no previous query or the query did not update an AUTO_INCREMENT value
		$responseArray['insert_id'] = $this->dbc->insert_id;
		
		// affected_rows only work for create, insert, update, replace, delete
		// For SELECT statements mysqli_affected_rows() works like mysqli_num_rows().
		$responseArray['affected_rows'] = $this->dbc->affected_rows;
		
        $dataArray = array();
		$tempSQLError = $this->dbc->error;
        // Debug mode - set sql, error 
		if($this->debug){
			$responseArray["sql"] = $sql_str;
			if(!empty($tempSQLError)){
				array_push($this->responseArray_errs, $tempSQLError);
			}		
		}else{
			$responseArray["sql"] = "turn on debug mode to unhidden";
			array_push($this->responseArray_errs, "turn on debug mode to unhidden");
		}
		// End - Debug mode
		if(empty($tempSQLError)){
			$responseArray['access_status'] = Core::$access_status['OK'];
		}
		else if(!empty($tempSQLError)){
			$responseArray['access_status'] = Core::$access_status["SqlError"];
		}
		else{
			$responseArray['access_status'] = Core::$access_status['Error'];
		}

		if(isset($result->num_rows)){
			if($fetchType==MYSQLI_NUM){
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) { // MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
					//if(isset($row["Default"]))
					//echo $row['Default'];
					array_push($dataArray, $row);
				}
				$responseArray['data'] = $dataArray;
			}else if($fetchType=MYSQLI_ASSOC){
			}
        }
		return $responseArray;
    }
    function queryResultToArrayVertical($sql_str, $fetchType=MYSQLI_NUM) {
    	$responseArray = Core::CreateResponseArray();
    	$responseArray_errs = $this->responseArray_errs;

		if($this->IsNullOrEmptyString($sql_str)){
			$sql_str = $this->sql_str;

			if($this->IsNullOrEmptyString($sql_str)){
				array_push($responseArray_errs, Core::$sys_err_msg["SQLNullOrEmpty"]);
				return $this->GetResponseArray();
			}
		}
        $result = $this->query($sql_str);
		
		//error handling, if num_rows = null
		// num_rows only work for select
		if(isset($result->num_rows))
			$responseArray['num_rows'] = $result->num_rows;
		else
			$responseArray['num_rows'] = 0;
		// insert_id return the lasted AUTO_INCREMENT field value.
		// Returns zero if no previous query or the query did not update an AUTO_INCREMENT value
		$responseArray['insert_id'] = $this->dbc->insert_id;
		
		// affected_rows only work for create, insert, update, replace, delete
		// For SELECT statements mysqli_affected_rows() works like mysqli_num_rows().
		$responseArray['affected_rows'] = $this->dbc->affected_rows;
		
        // Debug mode - set sql, error 
		if($this->debug){
			$responseArray["sql"] = $sql_str;
			if(isset($this->dbc->error)){
				array_push($responseArray_errs, $this->dbc->error);
			}
		}
		// End - Debug mode
		$tempSQLError = $this->dbc->error;
		if(empty($tempSQLError)){
			$responseArray['access_status'] = Core::$access_status['OK'];
		}
		else if(!empty($tempSQLError)){
			$responseArray['access_status'] = Core::$access_status["SqlError"];
		}
		else{
			$responseArray['access_status'] = Core::$access_status['Error'];
		}
        
		if(isset($result->num_rows)){
			if($fetchType==MYSQLI_NUM){
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) { // MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
					foreach($row as $key=>$value){
						if(!array_key_exists($key, $responseArray['data'])) {
							$responseArray['data'][$key] = array();
						}
						array_push($responseArray['data'][$key], $value);
					}
				}
			}else if($fetchType=MYSQLI_ASSOC){
			}
        }
		return $responseArray;
    }

    function beforeCreateInsertUpdateDelete($crudType){
    	// i must here to clear the responseArray['data'] created by setDataSchemaForSet()
    	$this->ResetResponseArray();
    	$isPermissionAllow = $this->DBManager_CheckPermission($crudType);

    	return $isPermissionAllow;
    }

    function afterCreateInsertUpdateDelete($crudType){
    }

    function DBManager_CheckPermission($crudTypePermission){
    	if($this->topRightToken)
    		return $this->topRightToken;
    	$isPermissionAllow = false;

    	if($this->enableSecurityModule){
    		if(class_exists("SecurityManager") && isset($this->securityManager)){
				//require_once 'SecurityManager.php';
				//$this->securityManager = new SecurityManager();
				$this->securityManager->SetDBContector($this->dbc);


		    	$isPermissionAllow = $this->securityManager->CRUD_PermissionCheck($crudTypePermission);
	    	}else{
	    		$isPermissionAllow = true;
	    	}
    	}else{
    		$isPermissionAllow = true;
    	}

    	if(!$isPermissionAllow){
			$this->responseArray['access_status'] = Core::$access_status["AuthorizationFail"];
			array_push($this->responseArray_errs, Core::$sys_err_msg["AuthorizationFail"]);
		}

    	return $isPermissionAllow;
    }

    public function IsColumnExists($columnName){
    	$search_array = $this->_;
    	// echo array_search($columnName, $search_array);
		if (array_key_exists($columnName, $search_array))
		    return array_search($columnName, $search_array) >=0 ? true : false;
		else
			return false;
    }

    /**
     * ExcelManager is record found in DB, insert for not found, update for found
     *
     */
    public function CheckKeyExists(){
    	$isKeyExists = false;

		$array = $this->_;
		$dataSchema = $this->dataSchema;
		$updateWhereColumn = "";
		$isPKMissing = true;

		$primaryKeySchema = $this->getPrimaryKeyName();
		
		// is primary key missing?
		foreach ($primaryKeySchema['data']['Field'] as $index => $value){
			if($this->IsNullOrEmptyString($array[$value])){
				$isPKMissing = $isPKMissing && true;
				//break;
			}else{
				$updateWhereColumn.="`".$value."` =".$this->GetSQLValueString($value)." AND ";
				$isPKMissing = false;
			}
		}

		// stop and return false if one/part of the Composite Primary Key are missing
		if($isPKMissing)
			return false;

		
		// stop and return error msg if PK missing
		/*
        if($isPKMissing){
			$missingPK = "";
			foreach ($primaryKeySchema['data']['Field'] as $index => $value){
				if($this->IsNullOrEmptyString($array[$value])){
					$missingPK.=$value." , ";
				}
			}
			$missingPK = rtrim($missingPK, " , ");
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["InsertFailNoPK"], $missingPK));
			$this->responseArray['access_status'] = Core::$access_status["Error"];
			return $this->GetResponseArray();
		}
        */
		
		$updateWhereColumn = rtrim($updateWhereColumn, " AND ");
		// mapping a update sql
		$sql_str = sprintf("SELECT * from `%s` where %s",
			$this->table,
        	$updateWhereColumn);
			
		$this->sql_str = $sql_str;
		$this->responseArray = $this->queryForDataArray();

		if($this->responseArray['num_rows'])
			$isKeyExists = true;
		else
			$isKeyExists = false;

		return $isKeyExists;
    }
	
    /**
     * TableManager basic and simple SELECT SQL Function
     *
     */
	public function select(){
		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

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
		if($isWhere){
			$whereSQL = rtrim($whereSQL, " and "); //would cut trailing 'and'.
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` where %s",
					$this->table,
					$whereSQL);
		}else{
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s`",
					$this->table);
		}

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();
		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}
    
	public function selectRange($fieldName, $selectionRange){
//		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

		$array = $fieldName;
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
				$whereSQL .= "`".$value."` BETWEEN \"". $selectionRange->$value->start . "\" and \"" . $selectionRange->$value->end . "\" or ";
				$isWhere = true;
			}
		}
		if($isWhere){
			$whereSQL = rtrim($whereSQL, " or "); //would cut trailing 'and'.
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` where %s",
					$this->table,
					$whereSQL);
		}else{
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s`",
					$this->table);
		}

		$this->sql_str = $sql_str;
//		if(!$isBeforeSuccess){
//			return $this->GetResponseArray();
//		}
		$this->responseArray = $this->queryForDataArray();
		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}
    
    public function selectPrimaryKeyList(){
        $responseArray = array();
        $responseArray = $this->getPrimaryKeyName();
        
        $responseArray["DataColumns"] = $this->dataSchemaCSharp;
        
        if($responseArray["num_rows"] > 0)
            $responseArray["KeyColumns"] = $responseArray["data"]["Field"];

        return $responseArray;
    }
	public function count(){
		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete("select");

		$array = $this->_;
		$dataSchema = $this->dataSchema;
		
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
		if($isWhere){
			$whereSQL = rtrim($whereSQL, " and "); //would cut trailing 'and'.
			$sql_str = sprintf("SELECT count(*) as count from `%s` where %s",
					$this->table,
					$whereSQL);
		}else{
			$sql_str = sprintf("SELECT count(*) as count from `%s`",
					$this->table);
		}

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();

		$this->afterCreateInsertUpdateDelete("select");
		return $this->GetResponseArray();
	}

/*
20161113, keithpoon, fixed: arguments cannot passed when use call_user_func_array in ConnectionManager.
Note that using call_user_func_* functions can't be used to call private or protected methods.
select(), count(), selectPage, insert(), update(), delete() must be public
// http://stackoverflow.com/questions/18526060/why-should-one-prefer-call-user-func-array-over-regular-calling-of-function
*/
	public function selectPage($tempOffset = 0, $tempLimit = 10){
		// $tempStep = $this->selectStep;
		// $tempLimit = $this->selectStep;
		// $tempTotalOffset = ($pageNum-1) * $tempOffset;
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
		if($isWhere){
			$whereSQL = rtrim($whereSQL, " and "); //would cut trailing 'and'.
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` where %s LIMIT %s OFFSET %s",
					$this->table,
					$whereSQL,
					$tempLimit,
					$tempTotalOffset);
		}else{
			$sql_str = sprintf("SELECT $tempSelectWhichCols from `%s` LIMIT %s OFFSET %s",
					$this->table,
					$tempLimit,
					$tempTotalOffset);
		}

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();
		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete("select");
		return $this->GetResponseArray();
	}

    /**
     * TableManager basic and simple INSERT SQL Function
     *
     */
	public function insert(){
		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

		$array = $this->_;
		$dataSchema = $this->dataSchema;

		$tableColumnSQL = "";
		$valuesSQL = "";
		$isSpecifiesColumn = false;
		$array_value = "";
		$isPKMissing = false;

		$primaryKeySchema = $this->getPrimaryKeyName();

		// is primary key missing?
		foreach ($primaryKeySchema['data']['Field'] as $index => $pkFieldName){
            // if primary key allow auto_increment, by pass the checking
            $isPKAutoIncrement = false;
            foreach ($dataSchema['data'] as $index => $value){
                $column = $value['Field'];
                $type = $value['Type'];
                if($column == $pkFieldName){
                    if($value['Extra']){
                        $isPKAutoIncrement = true;
                        break;
                    }
                }
            }
            
            if($isPKAutoIncrement)
                continue;
            
			if($this->IsNullOrEmptyString($array[$pkFieldName])){
				$isPKMissing = true;
				break;
			}
		}
		
		// stop and return error msg if PK missing
		if($isPKMissing){
			$missingPK = "";
			foreach ($primaryKeySchema['data']['Field'] as $index => $value){
				if($this->IsNullOrEmptyString($array[$value])){
					$missingPK.=$value." , ";
				}
			}
			$missingPK = rtrim($missingPK, " , ");
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["InsertFailNoPK"], $missingPK));
			$this->responseArray['access_status'] = Core::$access_status["Error"];
			return $this->GetResponseArray();
		}

		foreach ($dataSchema['data'] as $index => $value){
			$isColumnNullOrEmpty = false;
			$column = $value['Field'];
			$type = $value['Type'];

			if($this->IsSystemField($column)){
				continue;
			}
			
			// if value is null or empty
			if($this->IsNullOrEmptyString($array[$column])){
				$isColumnNullOrEmpty = true;
				// echo "IsNullOrEmpty";
                
                // 20170221, keithpoon, fixed: if the fk was null, don't assign empty
                if(!isset($array[$column]))
                    continue;
			}
			// if value exist are not null and empty
			else {
				$array_value = $this->GetSQLValueString($column);
			}
			// if column cannot null
			if(strtolower($value['Null']) == 'no'){
				// skip column if auto increment and have not defined the value
				if(strtolower($value['Extra']) == 'auto_increment'){
					if($this->IsNullOrEmptyString($array_value))
						continue;
				}
				// ignore column if the value = default value
				if(isset($value['Default']))
					if($array_value == $value['Default'])
						continue;
			}

			if(!$isColumnNullOrEmpty){
				$tableColumnSQL .= "`".$column."` , ";
				$valuesSQL .= $array_value." , ";
				$isSpecifiesColumn = true;
			}
		}

		$type = MYSQLI_NUM;
		$isClear = false;
		
		// add the createDate and createUser if table exists those fields
		$custom_sql_str = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
			$this->database_fyp,
			$this->table,
			Core::$reserved_fields["createDate"]);
		$this->sql_str = $custom_sql_str;
		$resultData = $this->queryForDataArray();
		if($resultData['num_rows'] > 0){
			$tableColumnSQL .= Core::$reserved_fields['createDate']." , ";
			$valuesSQL .= "'". date("Y-m-d H:i:s")."' , ";
		}

		// add the lastUpdateDate if table exists
		$custom_sql_str = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
			$this->database_fyp,
			$this->table,
			Core::$reserved_fields["lastUpdateDate"]);
		$this->sql_str = $custom_sql_str;
		$resultData = $this->queryForDataArray();

		if($resultData['num_rows'] > 0){
			$tableColumnSQL .= Core::$reserved_fields['lastUpdateDate']." , ";
			$valuesSQL .= "'". date("Y-m-d H:i:s")."' , ";
		}
		
		if($isSpecifiesColumn){
			// cut the trailing commas.
			$tableColumnSQL = rtrim($tableColumnSQL, " , ");
			$valuesSQL = rtrim($valuesSQL, " , ");
			
			$sql_str = sprintf("INSERT into %s ( %s ) values ( %s )",
				$this->table,
				$tableColumnSQL,
        		$valuesSQL);
			$this->sql_str = $sql_str;
			if(!$isBeforeSuccess){
				return $this->GetResponseArray();
			}
			$this->responseArray = $this->queryForDataArray();
		}else{
			// if all fields are not specifies any value
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["InsertFailFieldsNullOrEmpty"]));
			$this->responseArray['access_status'] = Core::$access_status["Error"];
		}

		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}

    /**
     * TableManager basic and simple UPDATE SQL Function
     * update but expect the key fields
     */
	public function update($ignoreTheLastDateCheck = false){
		if($this->$ignoreTheLastDateCheck)
			$ignoreTheLastDateCheck = true;

		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

		$array = $this->_;
		$dataSchema = $this->dataSchema;
		$updateSetColumn = "";
		$updateWhereColumn = "";
		$isPKMissing = false;

		$primaryKeySchema = $this->getPrimaryKeyName();
		
		// error handling
		// is primary key missing?
		foreach ($primaryKeySchema['data']['Field'] as $index => $value){
			if($this->IsNullOrEmptyString($array[$value])){
				$isPKMissing = true;
				break;
			}else{
				$updateWhereColumn.="`".$value."` =".$this->GetSQLValueString($value)." AND ";
			}
		}
		
		// stop and return error msg if PK missing
		if($isPKMissing){
			$missingPK = "";
			foreach ($primaryKeySchema['data']['Field'] as $index => $value){
				if($this->IsNullOrEmptyString($array[$value])){
					$missingPK.=$value." , ";
				}
			}
			$missingPK = rtrim($missingPK, " , ");
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["UpdateFailNoPK"], $missingPK));
			return $this->GetResponseArray();
		}
		// stop and return error msg if all fields except PK are null or empty
		$isAllColumnNullOrEmpty = true;
		$nullOrEmptyColumn = "";
		foreach ($array as $key=>$value){
			if($this->IsSystemField($key)){
				continue;
			}
            
            // 20170221, keithpoon, fixed: if the fk was null, don't assign empty
            if(!isset($value))
                continue;
            
			$isColumnAPK = array_search($key, $primaryKeySchema['data']['Field']);
			// array_search return key index if found, false otherwise
			if($isColumnAPK === false){

				$isAllColumnNullOrEmpty = $isAllColumnNullOrEmpty && $this->IsNullOrEmptyString($value);

				if(!$this->IsNullOrEmptyString($value)){
					$updateSetColumn.="`".$key."` =".$this->GetSQLValueString($key)." , ";
				}else{
                    // 20170111, keithpoon, also allowed to assign empty, if the user want to update the record from text to empty
                    $updateSetColumn.="`".$key."` ='', ";
				}
			}
		}
		
		if($isAllColumnNullOrEmpty){
			array_push($this->responseArray_errs, Core::$sys_err_msg["UpdateFailFieldsNullOrEmpty"]);
			return $this->GetResponseArray();
		}
		
		//// check table exists lastUpdateDate column, identify is this update action valid.
		$lastUpdateResponseArray = array();
		if(!$ignoreTheLastDateCheck){
			$custom_sql_str = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
				$this->database_fyp,
				$this->table,
				Core::$reserved_fields["lastUpdateDate"]);
			$this->sql_str = $custom_sql_str;
			$lastUpdateResponseArray = $this->queryForDataArray();
		}
		
		$isLastUpdateDateFound = false;
		if(!$ignoreTheLastDateCheck)
			if($lastUpdateResponseArray['num_rows'])
				$isLastUpdateDateFound = true;
		
		//$this->ResetResponseArray();
		
		// assign last update date and set the last update date condition
		if($isLastUpdateDateFound){
			$updateSetColumn .= Core::$reserved_fields["lastUpdateDate"]."='".date("Y-m-d H:i:s")."'";
			$updateWhereColumn .= Core::$reserved_fields["lastUpdateDate"] . 
				"=" . 
				$this->GetSQLValueString(Core::$reserved_fields["lastUpdateDate"]) . 
				" AND ";
		}
		//// END - check the lastUpdateDate column
		
		$updateWhereColumn = rtrim($updateWhereColumn, " AND ");
		$updateSetColumn = rtrim($updateSetColumn, " , ");		
		$nullOrEmptyColumn = rtrim($nullOrEmptyColumn, " , ");
		// mapping a update sql
		$sql_str = sprintf("UPDATE %s set %s where %s",
			$this->table,
			$updateSetColumn,
        	$updateWhereColumn);
			
		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();

		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}

    /**
     * TableManager basic and simple UPDATE SQL Function
     * update the instance value according to the parameter
     */
	public function updateAnyFieldTo($updateToMe){
		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

		$tableObject = $this->_;

		$updateSetColumn = "";
		$updateWhereColumn = "";
		$isPKMissing = false;
		
		// prepare the where condition query
		foreach ($updateToMe as $index => $value){
			if($this->IsNullOrEmptyString($tableObject[$value])){
				$isPKMissing = true;
				break;
			}else{
				$updateWhereColumn.=$value."=".$this->GetSQLValueString($value)." AND ";
			}
		}
		$updateWhereColumn = rtrim($updateWhereColumn, " AND ");
		// stop and return error msg if PK missing
		if($isPKMissing){
			$missingPK = "";
			foreach ($primaryKeySchema['data']['Field'] as $index => $value){
				if($this->IsNullOrEmptyString($tableObject[$value])){
					$missingPK.=$value." , ";
				}
			}
			$missingPK = rtrim($missingPK, " , ");
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["UpdateFailNoPK"], $missingPK));
			return $this->GetResponseArray();
		}
		// stop and return error msg if all fields except PK are null or empty
		$isAllColumnNullOrEmpty = true;
		$nullOrEmptyColumn = "";
		foreach ($tableObject as $key=>$value){
			$isColumnAPK = array_search($key, $primaryKeySchema['data']['Field']);
			// array_search return key index if found, false otherwise
			if($isColumnAPK === false){
				$isAllColumnNullOrEmpty = $isAllColumnNullOrEmpty && $this->IsNullOrEmptyString($value);
				if(!$this->IsNullOrEmptyString($value)){
					$updateSetColumn.=$key."=".$this->GetSQLValueString($key)." , ";
				}else{
					$nullOrEmptyColumn.=$key." , ";
				}
			}
		}
		
		$updateSetColumn = rtrim($updateSetColumn, " , ");
		
		$nullOrEmptyColumn = rtrim($nullOrEmptyColumn, " , ");
		if($isAllColumnNullOrEmpty){
			//return array("error"=>"All Fields all null or empty: cannot update all fields to null or empty, it doesn't make sense.");
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["UpdateFailFieldsNullOrEmpty"], $missingPK));
			return $this->GetResponseArray();
		}
		
		// mapping a update sql
		$sql_str = sprintf("UPDATE %s set %s where %s",
			$this->table,
			$updateSetColumn,
        	$updateWhereColumn);
		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();

		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}
	
    /**
     * TableManager basic and simple DELETE SQL Function
     *
     */
	public function delete(){
		$isBeforeSuccess = $this->beforeCreateInsertUpdateDelete(__FUNCTION__);

		$array = $this->_;
		$dataSchema = $this->dataSchema;

		$deleteWhereColumn = "";
		$isPKMissing = false;

		$primaryKeySchema = $this->getPrimaryKeyName();
        
		// error handling
		// is primary key missing?
		foreach ($primaryKeySchema['data']['Field'] as $index => $value){
			if($this->IsNullOrEmptyString($array[$value])){
				$isPKMissing = true;
				break;
			}else{
				$deleteWhereColumn.="`".$value."` =".$this->GetSQLValueString($value)." AND ";
			}
		}
		// stop and return error msg if PK missing
		if($isPKMissing){
			$missingPK = "";
			foreach ($primaryKeySchema['data']['Field'] as $index => $value){
				if($this->IsNullOrEmptyString($array[$value])){
					$missingPK.=$value." , ";
				}
			}
			$missingPK = rtrim($missingPK, " , ");
			$this->responseArray['access_status'] = Core::$access_status['Fail'];
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["DeleteFailNoPK"], $missingPK));
			return $this->GetResponseArray();
		}
		// stop the delete action if all fields are null or empty under the case if table no PK
		$isAllColumnNullOrEmpty = true;
		$nullOrEmptyColumn = "";
		foreach ($array as $key=>$value){
			if($this->IsSystemField($key)){
				continue;
			}
			$isColumnAPK = array_search($key, $primaryKeySchema['data']['Field']);
			// array_search return key index if found, false otherwise
			if($isColumnAPK === false){
				$isAllColumnNullOrEmpty = $isAllColumnNullOrEmpty && $this->IsNullOrEmptyString($value);
					if($this->IsNullOrEmptyString($value)){
						$nullOrEmptyColumn.=$key." , ";
					}
			}
		}
		if($isAllColumnNullOrEmpty && $isPKMissing){
			array_push($this->responseArray_errs, sprintf(Core::$sys_err_msg["DeleteFailFieldsNullOrEmpty"], $nullOrEmptyColumn));
			return $this->GetResponseArray();
		}

		//// check table exists lastUpdateDate column, identify is this update action valid.
		$custom_sql_str = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
			$this->database_fyp,
			$this->table,
			Core::$reserved_fields["lastUpdateDate"]);
		$this->queryForDataArray($custom_sql_str);
		
		$isLastUpdateDateFound = false;
		if($this->responseArray['num_rows'] > 0)
			$isLastUpdateDateFound = true;
				
		// assign last update date and set the last update date condition
		if($isLastUpdateDateFound){
			//$updateSetColumn .= Core::$reserved_fields["lastUpdateDate"]."='".date("Y-m-d H:i:s")."'";
			$deleteWhereColumn .= Core::$reserved_fields["lastUpdateDate"] . 
				"=" . 
				$this->GetSQLValueString(Core::$reserved_fields["lastUpdateDate"]) . 
				" AND ";
		}
		//// END - check the lastUpdateDate column
		
		$deleteWhereColumn = rtrim($deleteWhereColumn, " AND ");
		$nullOrEmptyColumn = rtrim($nullOrEmptyColumn, " , ");

		$sql_str = sprintf("DELETE from `%s` where %s",
			$this->table,
			$deleteWhereColumn);

		$this->sql_str = $sql_str;
		if(!$isBeforeSuccess){
			return $this->GetResponseArray();
		}
		$this->responseArray = $this->queryForDataArray();

		$this->responseArray['table_schema'] = $this->dataSchema['data'];

		$this->afterCreateInsertUpdateDelete(__FUNCTION__);
		return $this->GetResponseArray();
	}
	
	/*
	 * TableManager Initialize() function call, for futher action that is initialize getter and setter
	 */
	function setDataSchemaForSet($isClearResponseArrayDataIndex = false){
		$sql_str = sprintf("describe %s",
			$this->table);
		$this->sql_str = $sql_str;
		$this->dataSchema = $this->queryForDataArray();

		// extract data schema to increase readability
		$colDetailsIndex = array(
			"type", 
			"length", 
			"decimalPoint", 
			"null", 
			"key", 
			"default",
			"extra"
		);
		
		// Start - build the high readability dataSchema
		$this->dataSchemaCSharp = array();
		if(isset($this->dataSchema) && !empty($this->dataSchema["data"]))
		foreach($this->dataSchema["data"] as $arrayIndex => $columnDetails){
			$columnName = $columnDetails['Field'];
			$this->dataSchemaCSharp[$columnName] = array();
			
			$colType = $columnDetails['Type'];
				
			$tempColType = explode("(", $colType);
			
			if(isset($tempColType[0]))
				$colType = $tempColType[0];
			if(isset($tempColType[1]))
				$colLength = substr($tempColType[1], 0, strlen($tempColType[1])-1);
				
			$tempColLength = explode(",", $colLength);
			
			if(isset($tempColLength[0]))
				$colLength = $tempColLength[0];
			if(isset($tempColLength[1]))
				$colDecimalPoint = $tempColLength[1];
			else
				$colDecimalPoint = Null;
			
			foreach ($colDetailsIndex as $newArrayIndex){
				$this->dataSchemaCSharp[$columnName][$newArrayIndex] = null;
				
				switch ($newArrayIndex){
					case "type":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $colType;
						break;
					case "length":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $colLength;
						break;
					case "decimalPoint":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $colDecimalPoint;
						break;
					case "null":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $columnDetails['Null'];
						break;
					case "key":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $columnDetails['Key'];
						break;
					case "default":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $columnDetails['Default'];
						break;
					case "extra":
						$this->dataSchemaCSharp[$columnName][$newArrayIndex] = $columnDetails['Extra'];
						break;
				}
			}
		}
		// End - high readability dataSchema builded

		if($isClearResponseArrayDataIndex)
			$this->responseArray["data"] = array();
	}
	function getTableIndex(){
		$sql_str = sprintf("show index from `%s`",
			$this->table);
		$this->sql_str = $sql_str;
		$this->responseArray = $this->queryForDataArray();
		$this->tableIndex = $this->GetResponseArray();
	}
	function getColumnInfo($columnName){
		$columnInfo = array();

		$isFound = false;

		$dataSchema = $this->dataSchema['data'];

		if($dataSchema){
			foreach($dataSchema as $arrayIndex => $arrayVal){
				if($arrayVal['Field'] == $columnName){
					$columnInfo = $arrayVal;
					$isFound = true;
					break;
				}
			}
		}else{
			return false;
		}

		if(!$isFound)
			return false;

		return $columnInfo;
	}

	function getPrimaryKeyName(){
		// refer to http://mysql-0v34c10ck.blogspot.com/2011/05/better-way-to-get-primary-key-columns.html
		$showPrimaryKey_sql = sprintf("SELECT `COLUMN_NAME` AS `Field` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA` = '%s') AND (`TABLE_NAME` = '%s') AND (`COLUMN_KEY` = 'PRI')",
				$this->database_fyp,
				$this->table);
		$primaryKeySchema = $this->queryResultToArrayVertical($showPrimaryKey_sql);
		return $primaryKeySchema;
	}
	
	/**
	 * according to the data schema, create the index _ of the table object instance
	 */
	function setArrayIndex(){
		$dataSchema = $this->dataSchema['data'];
		if(!empty($dataSchema)){
			foreach($dataSchema as $index=>$value){
				$this->_[$value['Field']] = NULL;
			}
		}
	}
	
	/**
	 * according to the data schema, set the default value of the table object value
	 */
	function setDefaultValue(){
		$dataSchema = $this->dataSchema['data'];
		if(!empty($dataSchema)){
			foreach($dataSchema as $index=>$value){
                if(isset($value['Default']))
				    $this->_[$value['Field']] = $value['Default'];
				//echo $details['Field'] ." : ". (string)$details['Default'].$details['Extra']." <br>";
			}
			$this->isDefaultValueSet = true;
		}

	}
	// End - TableManager Initialize() function call
    function close() {
        $this->dbc->close();
    }
	
	/**
	 * Magic Methods: __destruct(), The destructor method will be called
	 * as soon as there are no other references to a particular object,
	 * or in any order during the shutdown sequence.
	 * 
	 */
    function __destruct() {
    	/*
    	print_r($this->dbc);
        if(isset($this->dbc)){
        		$this->dbc->close();
        }
        */
        // if(isset($this->dbc))
        // 	$this->dbc->close();

        // if(isset($this->dbc))
        // 	$this->close();
    }

    /**
     * Magic Methods: __set(), __set() is run when writing data to inaccessible properties.
     * so it is suitable for any initialization that the object may need before it is used.
	 * 
	 * @param string $name, The $name argument is the name of the property being interacted with.
	 * @param string $value, The __set() method's $value argument specifies the value the $name'ed property should be set to.
     */
    function __set($name, $value) {
        $method = 'Set' . ucfirst($name);
			if (method_exists($this, $method)) {
				// Top Priority - if TableNameManager have setName method
				$this->$method($value);
			}else if (array_key_exists($name, $this->_)) {//(isset($this->_[$name])) {
				// Second Priority - if TableNameManager have column name as $name
				//$this->_[$name] = $value;
				//if(!IsSystemField($name))
					$this->SetSQLValueString($name, $value);
			}else if (isset($this->$name)) {
				// Last Priority - if DatabaseManager have variable name as $name
				$this->$name = $value;
			}else {
				//throw new Exception('Manager cannot found and set table column or Parent variable!');
			}
    }
    
	/**
	 * Magic Methods: __get(), __get() is utilized for reading data from inaccessible properties.
	 * 
	 * @param string $name, The $name argument is the name of the property being interacted with.
	 * //may be controller need not to get
	 */
    function __get($name) {
        $method = 'get' . ucfirst($name);
		//if($this->issetDefaultValue){
        if (method_exists($this, $method)){
            return $this->$method();
        }else if (isset($this->_[$name])){
            //return $this->_[$name];
            return $this->GetSQLValueString($name);
        }else if (isset($this->$name)){
			return $this->$name;
        }
        //else
            //throw new Exception('Manager cannot found and get table column or Parent variable!');
		//}
    }
	
    public function __call($k, $args) {
        if (preg_match_all('/(set|get)([A-Z][a-z0-9]+)+/', $k, $words)) {
            $firstWord = $words[1][0]; // set or get
            $methodName = strtolower(array_shift($words[2]));
            //first word of property name

            foreach ($words[2] as $word) {
                $methodName .= ucfirst($word);
            }
            if (method_exists($this, $methodName)) {
                $methodObj = array(&$this, $methodName);
                if ($firstWord == 'set') {
                    call_user_func_array($methodObj, $args);
                    return;
                }
                else {
                    return call_user_func_array($methodObj, NULL);
                }
            }
        }
        throw new Exception('tableObject call undefined function() or property!');
    }
	
	/**
	 * assign $value to the TableManger.Object column
	 * 
	 * @param string $name columnName
	 * @param string $setValue a value you would like to assign to the TableManager.Object
	 * @return nothing, review is the value setted to the TableManager.Object, use print_r($this->_) to see TableManager.Object
	 */
	function SetSQLValueString($setColumn, $setValue)
	{
		$dataSchema = $this->dataSchema['data'];
		if(empty($dataSchema)){
			return;
		}
		if (PHP_VERSION < 6) {
			$setValue = get_magic_quotes_gpc() ? stripslashes($setValue) : $setValue;
		}
		
		//$setValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($setValue) : mysql_escape_string($setValue);
		// you may use mysqli->real_escape_string() to replace mysql_real_escape_string()
		
		$structure = $this->SearchDataType($dataSchema, 'Field', $setColumn);
		// $column structure
		//Array
		//(
		//	[0] => Array
		//		(
		//			[Field] => LoginID
		//			[Type] => varchar(255)
		//			[Null] => NO
		//			[Key] => UNI
		//			[Default] => 
		//			[Extra] => 
		//		)
		//)
		$type = $structure[0]['Type'];

		// for debug and checking,
		// i don't kown why it must coding as $type===, otherwise $type='datetime' will cased as float/double
		$typeCaseAs = "";

		//$hkTimeZone = new DateTimeZone("Asia/Hong_Kong");
		$defaultTimeZoneString = date_default_timezone_get();
		$hkTimeZone = new DateTimeZone($defaultTimeZoneString);

		//echo "I am a $type type.";
		switch (true) {
			case strpos($type, "char") !== FALSE:
			case strpos($type, "varchar") !== FALSE:
			case strpos($type, "text") !== FALSE:
				$typeCaseAs = "text";
				if(strpos($setValue, "'")==0 && strrpos($setValue, "'")==strlen($setValue)-1 && strlen($setValue)!=1){
					break;
				}
				// $setValue = ($setValue != "") ? "'" . $setValue . "'" : NULL;
				$setValue = $this->dbc->real_escape_string($setValue);

				$setValue = ($setValue != "") ? "'" . $setValue . "'" : NULL;
				break;
			//http://dev.mysql.com/doc/refman/5.0/en/integer-types.html
			case strpos($type, "tinyint") !== FALSE: // -128 to 127, 0 to 255
			case strpos($type, "smallint") !== FALSE: // -32768 to 32767, 0 to 65535
			case strpos($type, "mediumint") !== FALSE: // -8388608 to 8388607, 0 to 16777215
			case strpos($type, "int") !== FALSE: // -2147483648 to 2147483647, 0 to 4294967295
			case strpos($type, "bigint") !== FALSE: // -9223372036854775808 to 9223372036854775807, 0 to 18446744073709551615
				$setValue = ($setValue != "") ? intval($setValue) : NULL;
				$typeCaseAs = "integer";
				break;
			//http://dev.mysql.com/doc/refman/5.0/en/fixed-point-types.html
			//http://dev.mysql.com/doc/refman/5.0/en/floating-point-types.html
			case strpos($type, "float") !== FALSE:
			case strpos($type, "double") !== FALSE:
				$setValue = ($setValue != "") ? doubleval($setValue) : NULL;
				$typeCaseAs = "decimal";
				break;

			case $type==="date":
					$tmpDate = date_parse($setValue);
					if($tmpDate["error_count"] > 0)
						$setValue = date("Y-m-d"); // if convert with error, use the current date
					else
						$setValue = new DateTime($setValue);

				if(is_object($setValue)){
					$setValue->setTimezone($hkTimeZone);
					$setValue = $setValue->format("Y-m-d");
				}
				$setValue = "'" . $setValue . "'";
				$typeCaseAs = "date";
				break;

			case $type==="datetime":
			case $type==="timestamp":
				// convert string to date
				$tmpDate = date_parse($setValue);
				//print_r($tmpDate);
				if($tmpDate["error_count"] > 0){
					//$setValue = date("Y-m-d\TH:i:s+"); // if convert with error, use the current date
					$setValue = NULL;
					break;
				}else{
					$setValue = new DateTime($setValue);
				}

				// if(!is_null($setValue))
					if(is_object($setValue) && $setValue instanceof DateTime){
						$setValue->setTimezone($hkTimeZone);
						$setValue = $setValue->format("Y-m-d H:i:s");
					}
				$typeCaseAs = "datetime";
				break;
			case $type==="time":
					$tmpDate = date_parse($setValue);
					if($tmpDate["error_count"] > 0)
						$setValue = date("H:i:s"); // if convert with error, use the current date
					else
						$setValue = new DateTime($setValue);
				
				$setValue = $setValue->format("H:i:s");
				$setValue = "'" . $setValue . "'";
				$typeCaseAs = "time";
				break;
		}
		
		// if(strpos($setValue, '@')!==false)
		// echo "value in:$setValue, type:$type, entryType:$typeCaseAs"."<br>";

		$this->_[$setColumn] = $setValue;
	}
	
	/**
	 * return a specifiy column value
	 * 
	 * @param string $getColumn, column name
	 * @return string
	 */
	function GetSQLValueString($getColumn)
	{
		//return;
		$dataSchema = $this->dataSchema['data'];
		$structure = $this->SearchDataType($dataSchema, 'Field', $getColumn);
		$type = $structure[0]['Type'];
		$valueIn = $this->_[$getColumn];
		$valueOut = "NULL";
		
		// for debug and checking,
		// i don't kown why it must coding as $type===, otherwise $type='datetime' will cased as float/double
		$typeCaseAs = "";
		
		switch (true) {
			case strpos($type, "char") !== FALSE:
			case strpos($type, "varchar") !== FALSE:
			case strpos($type, "text") !== FALSE:
				if(strpos($valueIn, "'")==0 && strrpos($valueIn, "'")==strlen($valueIn)-1 && strlen($valueIn) != 1){
					$valueOut = $valueIn;
				}else{
					$valueOut = ($valueIn != "") ? "'" . $valueIn . "'" : NULL;
				}

				// $valueOut = $this->dbc->real_escape_string($valueIn);

				$typeCaseAs = "text";

				break;
				//http://dev.mysql.com/doc/refman/5.0/en/integer-types.html
			case $type === "tinyint": // -128 to 127, 0 to 255
			case $type === "smallint": // -32768 to 32767, 0 to 65535
			case $type === "mediumint": // -8388608 to 8388607, 0 to 16777215
			case strpos($type, "int") !== FALSE: // -2147483648 to 2147483647, 0 to 4294967295
			case $type === "bigint": // -9223372036854775808 to 9223372036854775807, 0 to 18446744073709551615
                // both are cannot identify the $valueIn is NULL, always convert the NULL to 0 and return
//                $valueOut = ($valueIn != "" && $valueIn != null) ? intval($valueIn) : "NULL";
//				$valueOut = (is_null($valueIn) || $valueIn == "" || $valueIn == null) ? echo "NULL" : intval($valueIn);
                $valueOut = is_int($valueIn) || gettype($valueIn)=="string" ? intval($valueIn) : "NULL";
                
				$typeCaseAs = "integer";
				break;
				//http://dev.mysql.com/doc/refman/5.0/en/fixed-point-types.html
				//http://dev.mysql.com/doc/refman/5.0/en/floating-point-types.html
			case $type==="float":
			case $type==="double":
//				$valueOut = ($valueIn != "") ? doubleval($valueIn) : NULL;
                $valueOut = is_float($valueIn) ? doubleval($valueIn) : "NULL";
				$typeCaseAs = "decimal";
				break;
			case $type==="date":
				if($this->IsNullOrEmptyString($valueIn)){
					//$valueOut = date("Y-m-d");
					$valueOut = NULL;
					return $valueOut;
					break;
				}else{
					$valueIn = trim($valueIn, "'");
					// convert string to date
					$tmpDate = date_parse($valueIn);
					if(count($tmpDate["errors"]) > 0)
						$valueOut = date("Y-m-d"); // if convert with error, use the current date
					else
						//$valueOut = $tmpDate->format("Y-m-d H:i:s");
						// mktime(hour,minute,second,month,day,year)
						$valueOut = date("Y-m-d", mktime(
							0, 
							0, 
							0, 
							$tmpDate["month"], 
							$tmpDate["day"], 
							$tmpDate["year"])
					);
				}
				$valueOut = "'" . $valueOut . "'";
				/*
				if(is_string($valueIn)){
					$valueOut = $valueIn;
				}else{
					$valueOut = ($valueIn != "") ? "'" . $valueIn . "'" : "NULL";
				}
				*/
				$typeCaseAs = "date";
				break;
			case $type==="datetime":
			case $type==="timestamp":
				if($this->IsNullOrEmptyString($valueIn)){
					//$valueOut = date("Y-m-d H:i:s");
					$valueOut = NULL;
					return $valueOut;
					break;
				}else{
					$valueIn = trim($valueIn, "'");
					// convert string to date
					$tmpDate = date_parse($valueIn);
					if(count($tmpDate["errors"]) > 0)
						$valueOut = date("Y-m-d H:i:s"); // if convert with error, use the current date
					else
						//$valueOut = $tmpDate->format("Y-m-d H:i:s");
						// mktime(hour,minute,second,month,day,year)
						$valueOut = date("Y-m-d H:i:s", mktime(
							$tmpDate["hour"], 
							$tmpDate["minute"], 
							$tmpDate["second"], 
							$tmpDate["month"], 
							$tmpDate["day"], 
							$tmpDate["year"])
					);
				}
				if(!is_null($valueOut))
					$valueOut = "'" . $valueOut . "'";

				$typeCaseAs = "datetime";

				break;
			case $type==="time":
				if($this->IsNullOrEmptyString($valueIn)){
					$valueOut = date("H:i:s");
				}else{
					$valueIn = trim($valueIn, "'");
					// convert string to date
					$tmpDate = date_parse($valueIn);
					if(count($tmpDate["errors"]) > 0)
						$valueOut = date("H:i:s"); // if convert with error, use the current date
					else
						//$valueOut = $tmpDate->format("Y-m-d H:i:s");
						// mktime(hour,minute,second,month,day,year)
						$valueOut = date("H:i:s", mktime(
							$tmpDate["hour"], 
							$tmpDate["minute"], 
							$tmpDate["second"])
					);
				}
				$valueOut = "'" . $valueOut . "'";
				$typeCaseAs = "time";
				break;
		}
		
//		echo "value in:$valueIn, type:$type, entryType:$typeCaseAs, value out:$valueOut";
		return $valueOut;
	}
	
	function IsSystemField($fields){
		$isSystemField = false;
		
		$isSystemField = array_search($fields, Core::$reserved_fields);
		return $isSystemField;
	}
	
	/**
	 *
	 * @param array $array a array or a nested array
	 * @param string $key search is the $key index exists in the array
	 * @param string $value find a array contain key index with $value value
	 * @return array, a array contains one or more array(s) which match $key as index and $value as value 
	 */
	function SearchDataType($array, $key, $value) {
		$results = array();
	
		if (is_array($array)) {
			if (isset($array[$key]) && $array[$key] == $value) {
				$results[] = $array;
			}
	
			foreach ($array as $subarray) {
				$results = array_merge($results, $this->SearchDataType($subarray, $key, $value));
			}
		}
	
		return $results;
	}
	
	/**
	 * 
	 * @param string $question input a sting
	 * @return boolean, true means that the string is null or empty otherwise false
	 */
	static function IsNullOrEmptyString($question){
		return (!isset($question) || trim($question)==='');
	}
}

?>
