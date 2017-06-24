<?php
header('Content-Type: application/json');
require_once '../model/SimpleTableManager.php';
require_once '../model/FormSubmitManager.php';

$tableManager = new SimpleTableManager();
$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$tableName = FormSubmit::POST("table");
$crudType = FormSubmit::POST("crud-type");

$tableManager = new SimpleTableManager();
$tableManager->Initialize($tableName);

$tableManager->debug = true;

if(isset($_POST["delete"]))
foreach($_POST["delete"] as $key => $value) {
	$tableManager->$key = $value;
}

//echo json_encode($tableManager->_);

echo json_encode($tableManager->delete());
//echo json_encode($tableManager->getTablePrimaryKey())

?>