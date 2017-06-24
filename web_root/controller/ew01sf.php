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

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $staffManager->$columnName = $value;
        }
        $responseArray = $staffManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$staffManager = new StaffManager();
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;

	$responseArray = $staffManager->selectPage($offsetRecords);
    
    $countResponseArray = $staffManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;
}


?>