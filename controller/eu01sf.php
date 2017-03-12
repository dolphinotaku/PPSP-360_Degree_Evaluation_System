<?php
function ExportData($httpRequest){
	$responseArray = array();

	$excelManager = new ExcelManager();
	$excelManager->Initialize();

	$requestData = new stdClass();
	$requestData = $httpRequest; //->Data->Header;

	// export multiple table
	$excelManager->AddTable("staff");
	
	// set excel header sequence
	$excelManager->SetSkipExportColumn("staff", "UserID");

	//$json_string = $excelManager->GetSkipExportColumn();
	//echo json_encode($json_string, JSON_PRETTY_PRINT);

	// export the excel in template for the user input data and import after
	// default is false
	$excelManager->isTemplate = false;

	// outputAsFileType default is xlsx
	// default is xlsx
	//$excelManager->outputAsFileType = "pdf";
	$excelManager->outputAsFileType = $requestData->ExportFileTypeAs;

	// custom the file name to be export, need not include extension
	//$excelManager->filename = "test-excel-export" . date('Y-m-d_His');
	$excelManager->filename = "Staff Profile Template";

	// call Export will download directly, cannot see the content
	//echo "export ".$excelManager->table." table in ".$excelManager->outputAsFileType." file";

	$responseArray = $excelManager->Export();
	return $responseArray;
}

function ImportData($httpRequest){
	$responseArray = array();
	$fileExistsInUploadFolder = false;
	$fileExistsInUserFolder = false;

	$importManager = new ExcelManager();
	$importManager->Initialize();
	
	$importManager->AddTable("staff");

	$responseArray = Core::CreateResponseArray();

	$requestData = new stdClass();
	$requestData = $httpRequest; //->Data->Header;

	$fileInfo = new stdClass();

	if(is_array($requestData->FileUploadedResult)){
		$fileInfo = $requestData->FileUploadedResult[0];
	}
	$excelFileLocation = $fileInfo->movedTo;

	// move file to user folder if user is valid
	$userID = "";
	$securityManager = new SecurityManager();

	if(file_exists($excelFileLocation))
		$fileExistsInUserFolder = true;

	if($fileExistsInUserFolder)
		$responseArray = $importManager->Import($excelFileLocation);

	// if(!$fileExistsInUploadFolder || !$fileExistsInUserFolder)
	// {
	// 	$responseArray['access_status'] = $importManager->access_status["Fail"];
	// 	$responseArray['error'] = "file was found at: $excelFileLocation";
	// }

	return $responseArray;
}
?>