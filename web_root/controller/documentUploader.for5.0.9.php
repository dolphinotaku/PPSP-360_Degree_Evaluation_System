<?php
require_once '../model/config.php';
//require_once '../model/ExcelManager.php';


$responseArray = array('name' => '', 'tmp_name'=> '','erorr'=>'','size'=>'','errorMsg'=>'');

if(isset($_FILES['uploadedFiles'])){
	$responseArray = $_FILES['uploadedFiles'];
	if(!file_exists($_FILES['uploadedFiles']['tmp_name']) || !is_uploaded_file($_FILES['uploadedFiles']['tmp_name'])) {
		// file not uploaded
	}else{
		$filename = $_FILES['uploadedFiles']['name'];
		$filenameWithoutExtension = basename($filename,".png");
		$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

		//$destination = BASE_UPLOAD. $filenameWithoutExtension .'-'.date('Y-m-d_His') .'.'. $fileExtension;
		$destination = BASE_UPLOAD.$filename;
		move_uploaded_file( $_FILES['uploadedFiles']['tmp_name'] , $destination );

		$responseArray['movedTo'] = $destination;
	}
}

// Error Messages Explained
// http://php.net/manual/en/features.file-upload.errors.php
$errorMsg = "";
switch ($responseArray['error']) {
	case '0':
		# code...
		break;
	case '1':
		$errorMsg = "The uploaded file exceeds the upload_max_filesize(".ini_get('upload_max_filesize').") directive in php.ini.";
		break;
	case '2':
		$errorMsg = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
		break;
	case '3':
		$errorMsg = "The uploaded file was only partially uploaded.";
		break;
	case '4':
		$errorMsg = "No file was uploaded.";
		break;
	case '5':
		# code...
		break;
	case '6':
		$errorMsg = "Missing a temporary folder. Introduced in PHP 5.0.3.";
		break;
	case '7':
		$errorMsg = "Failed to write file to disk. Introduced in PHP 5.1.0.";
		break;
	case '8':
		$errorMsg = "A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.";
		break;
	default:
		$errorMsg = "";
		break;
}
$responseArray['errorMsg'] = $errorMsg;

echo json_encode($responseArray, JSON_PRETTY_PRINT);
?>