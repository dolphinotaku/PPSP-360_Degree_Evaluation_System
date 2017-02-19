<?php
require_once '../model/SimpleSQLManager.php';
$webuserManager = new SimpleSQLManager();
//$webuserManager->debug = true;
$webuserManager->sql = "show tables";
$webuserManager->printDataAsVertical = true;
echo "<pre>";
echo json_encode($webuserManager->Execute(), JSON_PRETTY_PRINT);
echo "</pre>";
echo "<br>";

$webuserManager->sql = "describe senseiprofile";
$webuserManager->printDataAsVertical = false;
echo "<pre>";
echo json_encode($webuserManager->Execute(), JSON_PRETTY_PRINT);
echo "</pre>";
echo "<br>";

$webuserManager->sql = "SHOW KEYS FROM senseiprofile WHERE Key_name = 'PRIMARY'";
$webuserManager->printDataAsVertical = false;
echo "<pre>";
echo json_encode($webuserManager->Execute(), JSON_PRETTY_PRINT);
echo "</pre>";
echo "<br>";




?>