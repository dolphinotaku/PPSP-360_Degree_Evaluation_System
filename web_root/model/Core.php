<?php
define("BASE_ROOT", dirname(__FILE__)."/../");

define("BASE_RESOURSE", BASE_ROOT."resourse/");
define("BASE_CONTROLLER", BASE_ROOT."controller/");
define("BASE_MODEL", BASE_ROOT."model/");
define("BASE_TEMPLATE", BASE_ROOT."Templates/");
define("BASE_TEST", BASE_ROOT."test/");
define("BASE_3RD", BASE_ROOT."third-party/");
define("BASE_3RD_SOURCE", BASE_ROOT."third-part-sources/");
define("BASE_RESOURCE", BASE_ROOT."resourse/");
define("BASE_TEMPORARY", BASE_ROOT."temp/");

define("BASE_WORD_TEMPLATE", BASE_RESOURCE."word-template/");

define("BASE_UPLOAD", BASE_TEMPORARY."upload/");
define("BASE_EXPORT", BASE_TEMPORARY."export/");

define("INCLUDE_PHPMAILER", BASE_3RD."PHPMailer-5.2.9/PHPMailerAutoload.php");

define("IMPORTTYPE_INSERTANDUPDATE", "1");
define("IMPORTTYPE_INSERT", "2");
define("IMPORTTYPE_UPDATE", "3");

class CustomException extends Exception {}

class Core {
    public static $reserved_fields = array(
        "createUser"=>"createUser",
        "createDate"=>"createDate",
        "lastUpdateUser"=>"lastUpdateUser",
        "lastUpdateDate"=>"lastUpdateDate",
        "systemUpdateDate"=>"systemUpdateDate",
        "systemUpdateUser"=>"systemUpdateUser",
        "systemUpdateProgram"=>"systemUpdateProgram"
    );
    public static $access_status = array(
        "None" => "None",
        "OK" => "OK",
        "Duplicate" => "Duplicate",
        "Fail" => "Fail",
        "Error" => "Error",
        "TopOfFile" => "TopOfFile",
        "EndOfFile" => "EndOfFile",
        "Locked" => "Locked",
        "RecordNotFound" => "RecordNotFound",
        "SqlError" => "SqlExecuteError",
        "NoPermission" => "NoPermission",
        "AuthorizationFail" => "AuthorizationFail", // permissions (what you are allowed to do)
        "AuthenticationFail" => "AuthenticationFail"
    );
    public static $sys_err_msg = array(
        // registration
        "UserNameDuplicate" => "The username already in used by someone.",

        // sql level
        "SQLNullOrEmpty" => "sql query is empty",

        // insert error
        "InsertFailNoPK" => "Primary Key: (%s) have not set, cannot insert.",
        "InsertFailFieldsNullOrEmpty" => "All Fields are null or empty: insert a record with all null or empty cols, what you are doing?",

        // update error
        "UpdateFailNoPK" => "Primary Key: (%s) have not set, cannot update.",
        "UpdateFailFieldsNullOrEmpty" => "All Fields are null or empty: did't update all fields to null or empty, it doesn't make sense.",

        // delete error
        "DeleteFailNoPK" => "Primary Key: (%s) have not set, cannot delete.",
        "DeleteFailFieldsNullOrEmpty" => "All Fields are null or empty: cannot allocate a record to delete.",

        // Authorization || login
        "AuthorizationFail" => "Not enough permission to perform operation.", // permissions (what you are allowed to do)

        // Permission
        "NoPermission" => "Not enough permission to perform operation.",
        "AuthenticationFail" => "Invalid login ID or password.",

        "SecurityManagerNotFound" => "Security module enabled, but not inlcuded manager.php in Core.php", //. basename($_SERVER['PHP_SELF']),

        "TableNameNotFound" => "Table name was undefined."
    );
    
    public static function Install(){
        if (!file_exists(BASE_RESOURCE)) mkdir(BASE_RESOURCE, 0777, true);
        if (!file_exists(BASE_TEMPORARY)) mkdir(BASE_TEMPORARY, 0777, true);
        if (!file_exists(BASE_UPLOAD)) mkdir(BASE_UPLOAD, 0777, true);
        if (!file_exists(BASE_EXPORT)) mkdir(BASE_EXPORT, 0777, true);
        if (!file_exists(BASE_WORD_TEMPLATE)) mkdir(BASE_WORD_TEMPLATE, 0777, true);
    }
    
	public static function CreateResponseArray(){
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
    
	/**
	 *
	 * @param array $array a array or a nested array
	 * @param string $key search is the $key index exists in the array
	 * @param string $value find a array contain key index with $value value
	 * @return array, a array contains one or more array(s) which match $key as index and $value as value 
	 */
	public static function SearchDataType($array, $key, $value) {
		$results = array();
	
		if (is_array($array)) {
			if (isset($array[$key]) && $array[$key] == $value) {
				$results[] = $array;
			}
	
			foreach ($array as $subarray) {
				$results = array_merge($results, self::SearchDataType($subarray, $key, $value));
			}
		}
	
		return $results;
	}
    
    public static function IsNullOrEmptyString($question){
		return (!isset($question) || trim($question)==='');
	}
    public static function Is_Date($str){ 
        $str=str_replace('/', '-', $str);  //see explanation below for this replacement
        return is_numeric(strtotime($str));
    }
    
    public static function IsSystemField($fields){
		$isSystemField = false;
		
		$isSystemField = array_search($fields, self::$reserved_fields);
		return $isSystemField;
	}
}

?>