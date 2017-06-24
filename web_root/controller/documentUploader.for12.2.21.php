<?php
require_once '../model/config.php';
require_once '../model/Core.php';

function Random_string($length = 30) {
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < $length; $i++) {
        $key .= $keys[array_rand($keys)];
    }

    return $key;
}

// convert the $_FILES array to the cleaner (IMHO) array.
// http://php.net/manual/en/features.file-upload.multiple.php
// Array
// (
//     [name] => Array
//         (
//             [0] => foo.txt
//             [1] => bar.txt
//         )

//     [type] => Array
//         (
//             [0] => text/plain
//             [1] => text/plain
//         )

//     [tmp_name] => Array
//         (
//             [0] => /tmp/phpYzdqkD
//             [1] => /tmp/phpeEwEWG
//         )

//     [error] => Array
//         (
//             [0] => 0
//             [1] => 0
//         )

//     [size] => Array
//         (
//             [0] => 123
//             [1] => 456
//         )
// )
// Convert to
// Array
// (
//     [0] => Array
//         (
//             [name] => foo.txt
//             [type] => text/plain
//             [tmp_name] => /tmp/phpYzdqkD
//             [error] => 0
//             [size] => 123
//         )

//     [1] => Array
//         (
//             [name] => bar.txt
//             [type] => text/plain
//             [tmp_name] => /tmp/phpeEwEWG
//             [error] => 0
//             [size] => 456
//         )
// )
function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
}


$responseArray = array('name' => '', 'tmp_name'=> '','erorr'=>'','size'=>'','errorMsg'=>'');

if(isset($_FILES['file'])){
	$responseArray = $_FILES['file'];
	if(!file_exists($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
		// file not uploaded
	}else{
		$filename = $_FILES['file']['name'];
		$filenameWithoutExtension = basename($filename,".png");
		$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

		//$destination = BASE_UPLOAD. $filenameWithoutExtension .'-'.date('Y-m-d_His') .'.'. $fileExtension;
		$destination = BASE_UPLOAD.$filename;
		move_uploaded_file( $_FILES['file']['tmp_name'] , $destination );

		$responseArray['movedTo'] = $destination;

		// file Integrity
		$md5 = md5_file($destination);
	    $sha1 = sha1_file($destination);
	    // $md5 = md5(file_get_contents($destination));
	    // $sha1 = sha1(file_get_contents($destination));
		$responseArray['fileIntegrity-md5'] = $md5;
		$responseArray['fileIntegrity-sha1'] = $sha1;
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