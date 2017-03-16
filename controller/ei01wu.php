<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$webuserManager = new WebuserManager();
    
    return $webuserManager->selectPrimaryKeyList();
}

function ProcessData($requestData){
	return FindData($requestData);
}

function FindData($requestData){
	$webuserManager = new WebuserManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
	
	$responseArray = Core::CreateResponseArray();
	$responseArray["data"][0] = $loginData;
    $responseArray['access_status'] = Core::$access_status['OK'];
	
	return $responseArray;
}

function GetData($requestData){
	$webuserManager = new WebuserManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;
    
    $webuserManager->UserID = $userID;
    
	$responseArray = $webuserManager->select();
    
	return $responseArray;
}


?>