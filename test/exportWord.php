<?php
require_once '../model/Config.php';
require_once '../model/DatabaseManager.php';
require_once '../model/WordManager.php';
$wordManager = new WordManager();

$wordManager->table = "webuser";
//$excelManager->debug = true;
$wordManager->Initialize();
//$excelManager->isTemplate = false;
$wordManager->template = "../resourse/word-template/Template.docx";
$wordManager->filename = "hello_world_" . date('Y-m-d_His');

echo $wordManager->Export();
?>