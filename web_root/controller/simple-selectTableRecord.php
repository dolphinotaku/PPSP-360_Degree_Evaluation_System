<?php
header('Content-Type: application/json');
require_once '../model/FormSubmitManager.php';
require_once '../model/SimpleTableManager.php';
require_once '../model/SecurityManager.php';

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

if(isset($_GET["table"]))
	$tableName = $_GET["table"];
	
if(isset($_POST["table"]))
	$tableName = $_POST["table"];
	
//$tableName = FormSubmit::POST("table");
$crudType = FormSubmit::POST("crud-type");

$tableManager = new SimpleTableManager();
$tableManager->Initialize($tableName);

/*
$tableManager->debug = true;
//var_dump($_POST);

//$tableManager::set("originalName", "POON");
if(isset($_POST["read"]))
foreach($_POST["read"] as $key => $value) {
	$tableManager->$key = $value;
}
*/

echo json_encode($tableManager->select());
?>