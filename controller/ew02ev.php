<?php

// pageview for Individual Report to select the finished evaluation

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

	$responseArray = $evaluationManager->SelectFinishedEvaluation($offsetRecords);
    
    $countResponseArray = $evaluationManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;

}


?>