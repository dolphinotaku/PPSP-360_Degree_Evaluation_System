<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$evaProposalManager = new EvaProposalManager();
    
    return $evaProposalManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$evaProposalManager = new EvaProposalManager();

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