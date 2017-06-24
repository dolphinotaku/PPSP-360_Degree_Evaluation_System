<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$evaluationManager = new EvaluationManager();
    
    return $evaluationManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$evaluationManager = new EvaluationManager();

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $evaluationManager->$columnName = $value;
        }
        $responseArray = $evaluationManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$evaluationManager = new EvaluationManager();
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;

	$responseArray = $evaluationManager->selectPage($offsetRecords);
    
    $countResponseArray = $evaluationManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;

}


?>