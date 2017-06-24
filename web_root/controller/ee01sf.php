<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$staffManager = new StaffManager();
    
    return $staffManager->selectPrimaryKeyList();
}

function CreateData($requestData){
	$staffManager = new StaffManager();
    
	$createRows = new stdClass();
	$createRows = $requestData->Data->Header;
	foreach ($createRows as $keyIndex => $rowItem) {
		// $staffManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$staffManager->$columnName = $value;
		}
        
		$responseArray = $staffManager->insert();

	}
	return $responseArray;
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
    
	return $responseArray;

}

function UpdateData($requestData){
	$staffManager = new StaffManager();

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
	foreach ($updateRows as $keyIndex => $rowItem) {
		foreach ($rowItem as $columnName => $value) {
			$staffManager->$columnName = $value;
		}
        
		$responseArray = $staffManager->update();

	}
	return $responseArray;
}

function DeleteData($requestData){
	$staffManager = new StaffManager();

	$deleteRows = new stdClass();
	$deleteRows = $requestData->Data->Header;
	foreach ($deleteRows as $keyIndex => $rowItem) {
		foreach ($rowItem as $columnName => $value) {
			$staffManager->$columnName = $value;
		}
		$responseArray = $staffManager->delete();

	}
	return $responseArray;
}


?>