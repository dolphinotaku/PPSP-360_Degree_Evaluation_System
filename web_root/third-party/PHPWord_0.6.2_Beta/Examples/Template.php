<?php
require_once '../PHPWord.php';

$PHPWord = new PHPWord();

$document = $PHPWord->loadTemplate('Template.docx');

$document->setValue('Value1', 'Sun');
$document->setValue('Value2', 'Mercury');
$document->setValue('Value3', 'Venus');
$document->setValue('Value4', 'Earth');
$document->setValue('Value5', 'Mars');
$document->setValue('Value6', 'Jupiter');
$document->setValue('Value7', 'Saturn');
$document->setValue('Value8', 'Uranus');
$document->setValue('Value9', 'Neptun');
$document->setValue('Value10', 'Pluto');

$document->setValue('weekday', date('l'));
$document->setValue('time', date('H:i'));

$document->save('Solarsystem.docx');

		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment;filename="Solarsystem.docx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		
		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0
		
		//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$document->save('php://output');
		readfile("Solarsystem.docx");
?>