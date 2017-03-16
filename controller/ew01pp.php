<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$staffManager = new StaffManager();
    
    return $staffManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$staffManager = new StaffManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;
    
    $staffManager->UserID = $userID;
    
	$responseArray = $staffManager->select();
    
	return $responseArray;
}

function GetData($requestData){
	$staffManager = new StaffManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;
    
    $staffManager->UserID = $userID;
    
	$responseArray = $staffManager->select();
    
    $countResponseArray = $staffManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;
}


?>