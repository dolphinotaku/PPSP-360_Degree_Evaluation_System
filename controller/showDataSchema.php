<?php

require_once('../model/config.php');
header('Content-Type: application/json');
require_once '../model/SimpleSQLManager.php';
$sqlManager = new SimpleSQLManager();
if(isset($_GET["table"]))
$sqlManager->table = $_GET["table"];

if(isset($_POST["table"]))
$sqlManager->table = $_POST["table"];

$sqlManager->setDataSchemaForSet();
//echo json_encode($sqlManager->dataSchema, JSON_PRETTY_PRINT);
echo json_encode($sqlManager->dataSchemaCSharp, JSON_PRETTY_PRINT);

?>