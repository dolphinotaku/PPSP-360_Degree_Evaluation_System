<?php
// require_once 'DatabaseManager.php';

class SecurityManager extends DatabaseManager {

	/* 
		SecurityManager
		handle the user login and login action, remember me session algorithm.

		WebuserManager
		handle the registration, forgot and reset password and user management.
	*/
    protected $_ = array(
    );
    
    protected $hash;
	
	protected $table = "webuser";
	protected $tempLoginID = "";
	protected $tempPassword = "";
	//private $security_status = array();
	//private $access_status = array();
    
    function __construct() {
    	if (session_status() == PHP_SESSION_NONE) {
		    session_start();
		}
		parent::__construct();
		/*
		$this->security_status = array(
			// registration
			"UserNameDuplicate" => "The username already in used by someone.",

			// Authorization || login
			"AuthorizationFail" => "Not enough permission to perform operation.", // permissions (what you are allowed to do)

			// Permission
			"NoPermission" => "Not enough permission to perform operation.",
			"AuthenticationFail" => "Invalid login ID or password."
		);
		*/
		$this->hash = array(
    		"sha1" => "sha1", // 40 length
    		"sha256" => "sha256", // 64 length
    		"sha384" => "sha384", // 96 length
    		"sha512" => "sha512", // 128 length
    		"password_hash" => "password_hash"
    	);
    	/*
		$this->access_status = array(
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
		*/

		$this->Initialize();
    }
	function Initialize(){
//		$this->debug = true;

		parent::setDataSchemaForSet(false);
		parent::setArrayIndex();

		$this->isSelectAllColumns = false;
		// $this->selectWhichCols = "w.userID, loginID, status, isDisabled, activateDate, permissionID, fullName";
		$this->selectWhichCols = "activateDate, loginID, status, userID";

		$this->ResetResponseArray();
	}
	function ReInit(){
		$this->Initialize();
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}

	function Hash($phrase, $hashType = "sha1"){
		if(array_key_exists($hashType, $this->hash)){
			return hash($hashType, $phrase);
		}else{
			if($hashType == $this->hash["password_hash"])
				return password_hash($phrase, PASSWORD_DEFAULT);
			return false;
		}
	}

	function SetDBContector($dbc){
		parent::SetDBContector($dbc);
	}


	/**
	 * DatabaseManager insert/select/update/delete action before checking
	 *
	 * basic checking:
	 * if the user have not logged in - only select is avaiable
	 * if the user logged in - insert/select/update/delete are avaiable.
	 */
	function CRUD_PermissionCheck($crudType){
		if($this->topRightToken)
			return $this->topRightToken;
		$crudType = ($crudType == 'updateAnyFieldTo') ? 'update' : $crudType;
		$isLoggedIn = $this->isUserLoggedInBool();
		$returnValue = false;

		//if($this->enableSecurityModule)
		//$returnValue = true;

		if($this->enableSecurityModule && !$this->enableAdvanceSecurityCheck){

			// if($isLoggedIn)
			// 	$returnValue = true;

			$returnValue = $isLoggedIn;

			$returnValue = $crudType == "select" || $crudType == "read";
			/*
			switch ($crudType) {
				case 'insert':
				case 'update':
				case 'delete':
					//$this->GetRe["error"] = $this->access_status["AuthorizationFail"];
					break;
				default:
					$returnValue = true;
					break;
			}
			*/

		}else if($this->enableSecurityModule && $this->enableAdvanceSecurityCheck){
			if($isLoggedIn){
				// check the webuser PermissionID, PermissionGroup, PermissionRight table
				$selectGlobalRightCols = "`permissionGroupName` as 'permsGrpName', `globalCreateRight` as 'insert', `globalReadRight` as 'select', `globalUpdateRight` as 'update', `globalDeleteRight` as 'delete'";
				$selectIndividualRightCols = "`createRight` as 'insert', `readRight` as 'select', `updateRight` as 'update', `deleteRight` as 'delete'";
				$permsGroupName = "N/A";
				$tempCR = false;
				$tempRR = false;
				$tempUR = false;
				$tempDR = false;

				// get permissionID
				$permsID = $_SESSION['USER_PermissionID'];

				// check the global CRUD right
					$sql_str = sprintf("SELECT $selectGlobalRightCols FROM `permission` WHERE permissionID = %s",
							$permsID);
				//$responseArray = $this->queryForDataArray($sql_str);
				$this->sql_str = $sql_str;
				$responseArray = $this->queryForDataArray();
				if($responseArray['num_rows']>0){
					$permsGroupName = $responseArray['data'][0]['permsGrpName'];
					$tempCR = $responseArray['data'][0]['insert'] == 'A';
					$tempRR = $responseArray['data'][0]['select'] == 'A';
					$tempUR = $responseArray['data'][0]['update'] == 'A';
					$tempDR = $responseArray['data'][0]['delete'] == 'A';
				}


				// check the break down right
				$tempFunctionName = "N/A";
				$tempCtrlName = $this->permsCtrlName;
					$sql_str = sprintf("SELECT $selectIndividualRightCols FROM `permissionGroupRight` WHERE (permissionID = '%s' or permissionGroupName = '%s') AND (functionName = '%s' or controllerName = '%s')",
							$permsID,
							$permsGroupName,
							$tempFunctionName,
							$tempCtrlName);


				$this->sql_str = $sql_str;
				$responseArray = $this->queryForDataArray();

				if($responseArray['num_rows']>0){
					$tempCR = $responseArray['data'][0]['insert'] == 'A';
					$tempRR = $responseArray['data'][0]['select'] == 'A';
					$tempUR = $responseArray['data'][0]['update'] == 'A';
					$tempDR = $responseArray['data'][0]['delete'] == 'A';
				}

				switch ($crudType) {
					case 'insert':
						$returnValue = $tempCR;
						break;
					case 'select':
						$returnValue = $tempRR;
						break;
					case 'update':
						$returnValue = $tempUR;
						break;
					case 'delete':
						$returnValue = $tempDR;
						break;
					default:
						$returnValue = false;
						break;
				}
			}

		}
		return $returnValue;
	}

	function Registration(){
		return $this->insert();
	}
	
	function DoLogin($username, $password){
		//$this->tempLoginID = $this->GetRealEscapeString($username);//mysql_real_escape_string($username);
		//$this->tempPassword = $this->GetRealEscapeString($password);//mysql_real_escape_string($password);

		$this->tempLoginID = $this->GetRealEscapeString($username);
		$this->tempPassword = $this->GetRealEscapeString($password);

		if($this->IsNullOrEmptyString($this->tempLoginID) || $this->IsNullOrEmptyString($this->tempPassword))
		{
			return $this->GetLoginData();
		}else{
			$this->LoginID = $this->tempLoginID;
			$this->Password = $this->Hash($this->tempPassword);
			$tmpResponseArray = $this->select();
			
			$webuserManager = new WebuserManager();
			$webuserManager->LoginID = $this->tempLoginID;
			$webuserManager->Password = $this->Hash($this->tempPassword);
			$tmpResponseArray = $webuserManager->SelectGeneralUserInfo();

			$tmpResponseArray["SESSION_ID"] = 0;
			if($tmpResponseArray["num_rows"]==1){
				$this->CreateSession($tmpResponseArray["data"][0]);
				$tmpResponseArray["SESSION_ID"] = $_SESSION['SESSION_ID'];
			}else{
				$tmpResponseArray["access_status"] = $this->access_status["AuthenticationFail"];

				if(!$this->IsNullOrEmptyString($tmpResponseArray["error"])){
				 	// $tmpResponseArray["error"] = $this->sys_err_msg["AuthenticationFail"];
				}
			}
//			echo json_encode($tmpResponseArray);
			return $tmpResponseArray;
		}
	}

	// Overwriting the insert function in DatabaseManager
	function DBManager_CheckPermission($crudType){
		return true;
	}

	function CreateSession($dataRow){
		$_SESSION['SESSION_ID'] = session_id();
		$_SESSION['USER_ID'] = $dataRow["UserID"];
		$_SESSION['LOGIN_ID'] = $dataRow["LoginID"];
		$_SESSION['USER_Status'] = $dataRow["Status"];
		$_SESSION['USER_AccountType'] = $dataRow["AccountType"];
//		$_SESSION['USER_IsDisabled'] = $dataRow["isDisabled"];
//		$_SESSION['USER_PermissionID'] = $dataRow["permissionID"];
        $_SESSION['USER_login_status'] = 1;
	}

	function GetSessionData(){
		$sessionArray = [];
		return $_SESSION;
	}

	function GetLoginData(){
		/*
		$responseArray = array("isLogin" => 0);
		if(isset($_SESSION['USER_login_status']) && $_SESSION['USER_login_status'] == 1)
		{
			$responseArray['isLogin'] = 1;
		}
		*/
		$responseArray = $this->ResetResponseArray();

		$responseArray['num_rows'] = 0;
		$responseArray['isLogin'] = 0;
		$responseArray['error'] = "";
		//$sessionArray = $this->GetSessionData();
		if(isset($_SESSION['USER_login_status']) && $_SESSION['USER_login_status'] == 1)
		{
			$responseArray['num_rows'] = 1;
			$responseArray['isLogin'] = 1;
			$sessionArray = $this->GetSessionData();

			$responseArray = array_merge((array)$responseArray, (array)$sessionArray);
		}
		return $responseArray;
	}

	function CheckSessionID($tmpSessionID){
		$isSessionIDValid = true;

		if(isset($_SESSION['SESSION_ID']) && $_SESSION['SESSION_ID'] == $tmpSessionID){

		}else{
			
		}

		return $isSessionIDValid;
	}

	function isUserLoggedInBool(){
		if(isset($_SESSION['USER_login_status']) && $_SESSION['USER_login_status'] == 1)
		{
			return true;
		}
		return false;
	}

	function DoLogout(){
        // delete the session of the user
        //$responseArray = array("isLogin" => 0);
        $responseArray = $this->ResetResponseArray();
        try{
        	$_SESSION = array();
        	session_destroy();
        	$responseArray['isLogin'] = 0;
    	}catch(Exception $e) {
    		$responseArray["error"] = $e;
    	}
    	return $responseArray;

	}
    
    function __isset($name){
        return isset($this->_[$name]);
    }
}
?>