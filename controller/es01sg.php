<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$positionManager = new SimpleTableManager();
    $positionManager->Initialize("staffgrade");
    
    return $positionManager->selectPrimaryKeyList();
}

function CreateData($requestData){
	$positionManager = new SimpleTableManager();
    $positionManager->Initialize("staffgrade");
    
	$createRows = new stdClass();
	$createRows = $requestData->Data->Header;
	foreach ($createRows as $keyIndex => $rowItem) {
		// $positionManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$positionManager->$columnName = $value;
		}
		$responseArray = $positionManager->insert();

	}
	return $responseArray;
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
    
	return $responseArray;

}

function UpdateData($requestData){
	$positionManager = new SimpleTableManager();

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
	foreach ($updateRows as $keyIndex => $rowItem) {
        $positionManager->Initialize("staffgrade");
		foreach ($rowItem as $columnName => $value) {
			$positionManager->$columnName = $value;
		}
		$responseArray = $positionManager->update();

	}
	return $responseArray;
}

function DeleteData($requestData){
	$positionManager = new SimpleTableManager();

	$deleteRows = new stdClass();
	$deleteRows = $requestData->Data->Header;
	foreach ($deleteRows as $keyIndex => $rowItem) {
        $positionManager->Initialize("staffgrade");
		foreach ($rowItem as $columnName => $value) {
			$positionManager->$columnName = $value;
		}
		$responseArray = $positionManager->delete();

	}
	return $responseArray;
}


?>