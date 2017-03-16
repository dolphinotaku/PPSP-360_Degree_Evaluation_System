<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$departmentManager = new SimpleTableManager();
    $departmentManager->Initialize("department");
    
    return $departmentManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$departmentManager = new SimpleTableManager();
    $departmentManager->Initialize("department");

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $departmentManager->$columnName = $value;
        }
        $responseArray = $departmentManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$departmentManager = new DepartmentManager();
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;

	$responseArray = $departmentManager->selectPage($offsetRecords);
    
    $countResponseArray = $departmentManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;

}
?>