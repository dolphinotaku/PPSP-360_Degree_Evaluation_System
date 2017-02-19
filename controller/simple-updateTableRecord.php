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

if(isset($_POST["updateTo"]))
foreach($_POST["updateTo"] as $key => $value) {
	$tableManager->$key = $value;
}

echo json_encode($tableManager->update());
//echo json_encode($tableManager->getTablePrimaryKey())

?>