<?php
require_once '../model/config.php';
require_once '../model/DatabaseManager.php';
require_once '../model/ExcelManager.php';
require_once '../model/SimpleTableManager.php';

// echo "<html><head><meta charset='utf-8'>";
// echo "<div style='font-family:sun-exta;'>中文 sun-exta font style</div>";

	$excelManager = new ExcelManager();
	$excelManager->Initialize();

	$excelManager->AddTable("CardContent");
	$excelManager->outputAsFileType = "pdf";
	// $excelManager->outputAsFileType = "xlsx";

	$responseArray = $excelManager->DirectExport();

	// require_once '../third-party\MPDF\mpdf_6.1.0/mpdf.php';

	// // instantiate and use the dompdf class
	// $mpdf = new mPDF();

	// $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>
	// <body>
	//     <p style="font-family: \'Sun-ExtA\';">献给母亲的爱</p>
	//     <p style="font-family: \'Arial Unicode MS\';">你好嗎Join</p>
	//     <p style="font-family: \'Serif\';">Serif 細明體</p>
	//     <p style="font-family: \'sans-serif\';">sans-serif 新細明體</p>
	//     <p>未指名字體 Test</p>

	// </body>
	// </html>';

	// $mpdf->WriteHTML($html);

	// $mpdf->Output();
?>