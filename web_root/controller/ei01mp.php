<?php

function ProcessData(){
	$evaProposalManager = new EvaProposalManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    $loginID = $loginData["LOGIN_ID"]; $loginID = strtoupper($loginID);
    
    $evaProposalManager->Evaluator = $loginID;
    
	$responseArray = $evaProposalManager->select();
    
    $countResponseArray = $evaProposalManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;
}

function GetTableStructure(){
	$evaProposalManager = new EvaProposalManager();
    
    return $evaProposalManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$evaProposalManager = new EvaProposalManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $evaProposalManager->$columnName = $value;
        }
        $responseArray = $evaProposalManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$evaProposalManager = new EvaProposalManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    $loginID = $loginData["LOGIN_ID"]; $loginID = strtoupper($loginID);
    
    $evaProposalManager->Evaluator = $loginID;
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;
	$numOfRecordPerPage = $requestData->PageRecordsLimit;

	$responseArray = $evaProposalManager->selectPage($offsetRecords, $numOfRecordPerPage);
    
    $countResponseArray = $evaProposalManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;

}
?>