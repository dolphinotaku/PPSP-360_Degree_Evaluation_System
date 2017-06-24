<?php
date_default_timezone_set('Asia/Hong_Kong');

require_once 'config.php';
require_once 'FormSubmitManager.php';
require_once 'ManagerLoader.php';

header('Content-Type: application/json', true, 200);

// $currentFilename = basename(__FILE__);

// foreach (scandir(dirname(__FILE__)) as $filename) {
//     $path = dirname(__FILE__) . '/' . $filename;

//     if($filename == $currentFilename)
//     	continue;

//     if($filename == "config.php")
//     	continue;

//     if($filename == "FormSubmitManager.php")
//     	continue;

//     if($filename == "DatabaseManager.php")
//     	continue;

//     if(strpos($filename, "Manager") < 0)
//     	continue;

//     if (is_file($path)) {
//         // echo $path."<br>";
//         require_once $path;
//     }
// }

// new Object();
// Standard Defined Classes in PHP
$requestJson = new stdClass();
$responseData = new stdClass();
$responseData->Status = "success";
$responseData->Message = [];

$sqlResultData = [];

$postBody = file_get_contents('php://input');
$requestJson = json_decode($postBody);
$action = $requestJson->Action;

$tableManager = new stdClass();

$isFileExists = false;

// securityManager
try{
	switch ($action) {
		case 'Login':
			Login();
			break;
		case 'CheckLogin':
			CheckLogin();
			break;
		case 'Logout':
			Logout();
			break;
		default:
			// Create Table Manager
			try{
				if(!isset($requestJson->Table))
					throw new CustomException('HTTP request Table parameter was not found.');

				$prgmName = $requestJson->Table;
				$prgmPath = "../controller/$prgmName.php";

				$isFileExists = file_exists($prgmPath);

				if($isFileExists){
					require_once $prgmPath;
				}else{
					$responseData->Status = "PrgmNotFound";
					array_unshift($responseData->Message, "Controller not found, $prgmPath");
				}

			}catch (CustomException $e) {
				$responseData->Status = "CustomException";
				array_unshift($responseData->Message, $e->getMessage());
			}catch (Exception $e) {
				$responseData->Status = "PrgmNotFound";
				array_unshift($responseData->Message, "TableManager not found, $prgmPath.");
			}
			break;
	}
}catch (Exception $e) {
	$responseData->Status = "Error";
	array_unshift($responseData->Message, $e->getMessage());
}

if(!$isFileExists){
	echo json_encode($responseData);
	return;
}

if($action == "Login" || $action == "CheckLogin" || $action == "Logout"){
	$responseData =  (object)array_merge((array)$responseData, (array)$sqlResultData);
	//echo json_encode($responseData);
	return;
}

// Validate Action
$isProgramExists = false;
$funcName = "";
switch ($action) {
	// get single record
	case 'GetTableStructure':
		$funcName = "GetTableStructure";
		break;
	// get single record
	case 'FindData':
		$funcName = "FindData";
		break;
	// get records in result set
	case 'GetData':
		$funcName = "GetData";
		break;
	case 'CreateData':
		$funcName = "CreateData";
		break;
	case 'UpdateData':
		$funcName = "UpdateData";
		break;
	case 'DeleteData':
		$funcName = "DeleteData";
		break;
	case 'ImportData':
		$funcName = "ImportData";
		break;
	case 'ExportData':
		$funcName = "ExportData";
		break;
    case 'ProcessData':
        $funcName = "ProcessData";
        break;
	case 'IsKeyExists':
		$funcName = "IsKeyExists";
		break;
	default:
		$responseData->Status = "UnkownAction";
		array_unshift($responseData->Message, "Unkown action: $action");
		break;
}

// Validate function exists
$isProgramExists = function_exists($funcName);
if(!$isProgramExists){
	$responseData->Status = "FuncNotFound";
	array_unshift($responseData->Message, "Function $funcName() not found in $prgmName");

	$responseData =  (object)array_merge((array)$responseData, (array)$sqlResultData);

	echo json_encode($responseData);

	return;
}

// get the HTTP method, path and body of the request
$requestMethod = $_SERVER['REQUEST_METHOD'];
// retrieve request data
//$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

$requestData = [];
switch ($requestMethod) {
	case 'GET':
		$requestData = $_GET;
		break;
	case 'HEAD':
		# code...
		break;
	case 'POST':
		$requestData = $_POST;
		break;
	case 'PUT':
		# code...
		break;
	
	default:
		# code...
		break;
}

if($requestData == null || empty($requestData))
	$requestData = $requestJson;

/*
Note that using call_user_func_* functions can't be used to call private or protected methods.
*/
try{
	switch ($action) {
		// get single record
		case 'GetTableStructure':
			$sqlResultData = call_user_func($funcName);
			break;
		// get single record
		case 'FindData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			break;
		// get records in result set
		case 'GetData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			break;
		case 'CreateData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			if($sqlResultData['ActionResult']['access_status'] == "OK"){
				$sqlResultData['Message'] = "Data has been successfully created.";
			}else{
//				$sqlResultData['Message'] = "Data create failure.";
                $sqlResultData['Message'] = $sqlResultData['ActionResult']['error'];
			}
			break;
		case 'UpdateData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			if($sqlResultData['ActionResult']['access_status'] == "OK"){
				$sqlResultData['Message'] = "Data has been successfully updated.";
			}else{
//				$sqlResultData['Message'] = "Data update failure.";
                $sqlResultData['Message'] = $sqlResultData['ActionResult']['error'];
			}
			break;
		case 'DeleteData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			if($sqlResultData['ActionResult']['access_status'] == "OK"){
				$sqlResultData['Message'] = "Data has been successfully deleted.";
			}else{
//				$sqlResultData['Message'] = "Data delete failure.";   
                $sqlResultData['Message'] = $sqlResultData['ActionResult']['error'];
			}
			break;
		case 'ImportData':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			break;
		case 'ExportData':
			// $sqlResultData['ActionResult'] = array();
			// $sqlResultData['ActionResult']['access_status'] = "OK";
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			//$sqlResultData['ActionResult'] = call_user_func($funcName);
			// print_r(call_user_func($funcName));
			break;
        case 'ProcessData':
            $sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
            break;
		case 'IsKeyExists':
			$sqlResultData['ActionResult'] = call_user_func_array($funcName, array($requestData));
			// call_user_func($funcName);
			break;
		
		default:
			# code...
			break;
	}
	
	if($action != "GetTableStructure")
        if($sqlResultData['ActionResult']['access_status'] != "OK"){
            $responseData->Status = "Fail";
        }

}catch (Exception $e) {
	$responseData->Status = "Error";
	array_unshift($responseData->Message, $e->getMessage());
}

function Login(){
	$securityManager = new SecurityManager();
	global $requestJson;
	global $sqlResultData;
	global $responseData;

	$username = $requestJson->UserCode;
	$password = $requestJson->Password;

	if($securityManager->isUserLoggedInBool()){
		$sqlResultData = ($securityManager->GetLoginData());
	}else{
		$sqlResultData = ($securityManager->DoLogin($username, $password));
	}

	if($sqlResultData["num_rows"] != 1){
		$responseData->Status = "LoginFail";
		$responseData->Message = $sqlResultData["error"];
	}else{
		$responseData->Status = "LoginSuccess";
	}
}

function CheckLogin(){
	$securityManager = new SecurityManager();
	global $requestJson;
	global $sqlResultData;
	global $responseData;

	if($securityManager->isUserLoggedInBool()){
		$sqlResultData = ($securityManager->GetLoginData());
		$responseData->Status = "LoginSuccess";
		$responseData->Message = "Already login";
	}else{
		$responseData->Status = "LoginFail";
		$responseData->Message = "Not signed in";
	}
}

function Logout(){
	$securityManager = new SecurityManager();
	global $sqlResultData;
	$sqlResultData = $securityManager->DoLogout();
}

$responseData =  (object)array_merge((array)$responseData, (array)$sqlResultData);

echo json_encode($responseData);


?>