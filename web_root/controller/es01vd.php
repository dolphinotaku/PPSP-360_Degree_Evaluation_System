<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function GetTableStructure(){
	$vendorManager = new SimpleTableManager();
	$vendorManager->Initialize("vendor");

	return $vendorManager->selectPrimaryKeyList();
}

function CreateData($requestData){
	$vendorManager = new SimpleTableManager();
	$vendorManager->Initialize("vendor");

	$createRows = new stdClass();
	$createRows = $requestData->Data->Header;
	foreach ($createRows as $keyIndex => $rowItem) {
		// $vendorManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$vendorManager->$columnName = $value;
		}
		$vendorManager->UserID = null;
		$responseArray = $vendorManager->insert();

	}
	return $responseArray;
}

function FindData($requestData){
	$vendorManager = new SimpleTableManager();
	$vendorManager->Initialize("vendor");

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;

	foreach ($updateRows as $keyIndex => $rowItem) {
		foreach ($rowItem as $columnName => $value) {
			$vendorManager->$columnName = $value;
		}
		$responseArray = $vendorManager->select();
		break;
	}

	return $responseArray;
}

function GetData($requestData){
	$vendorManager = new SimpleTableManager();
	$vendorManager->Initialize("vendor");

	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;

	$responseArray = $vendorManager->selectPage($offsetRecords);

	return $responseArray;

}

function UpdateData($requestData){
	$vendorManager = new SimpleTableManager();

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
	foreach ($updateRows as $keyIndex => $rowItem) {
		$vendorManager->Initialize("vendor");
		foreach ($rowItem as $columnName => $value) {
			$vendorManager->$columnName = $value;
		}
		$responseArray = $vendorManager->update();

	}
	return $responseArray;
}

function DeleteData($requestData){
	$vendorManager = new SimpleTableManager();

	$deleteRows = new stdClass();
	$deleteRows = $requestData->Data->Header;
	foreach ($deleteRows as $keyIndex => $rowItem) {
		$vendorManager->Initialize("vendor");
		foreach ($rowItem as $columnName => $value) {
			$vendorManager->$columnName = $value;
		}
		$responseArray = $vendorManager->delete();

	}
	return $responseArray;
}


?>