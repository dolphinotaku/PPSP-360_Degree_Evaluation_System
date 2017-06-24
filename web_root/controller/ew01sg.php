<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$positionManager = new SimpleTableManager();
    $positionManager->Initialize("staffgrade");
    
    return $positionManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$positionManager = new SimpleTableManager();
    $positionManager->Initialize("staffgrade");

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $positionManager->$columnName = $value;
        }
        $responseArray = $positionManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$positionManager = new SimpleTableManager();
    $positionManager->Initialize("staffgrade");
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;

	$responseArray = $positionManager->selectPage($offsetRecords);
    
    $countResponseArray = $positionManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;
}

?>