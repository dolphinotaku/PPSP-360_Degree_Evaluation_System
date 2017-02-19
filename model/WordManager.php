<?php
// require_once 'DatabaseManager.php';
require_once '../third-party/PHPWord_0.6.2_Beta/PHPWord.php';

//require_once dirname(__FILE__).'/../globalVariable.php';

class WordManager extends DatabaseManager {

	protected $PHPWord;
	/* 
		Construct attributes (table column)
		for insert(), update(), delete()
	*/
    protected $_ = array(
    );
    
    protected $isTemplate = false;
	protected $isDeleteMergedDoc;
	protected $filename = "";
	protected $filenamePost = "";
	protected $outputAsFileType;
	
	protected $template = "Template.docx";
	
	private $docFullExportPath = "";
    
    protected $fileType = array(
    		"doc" => "doc",
    		"docx" => "docx"
    );
	
	protected $table = "";
    
    function __construct() {
		parent::__construct();
		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		
		$this->PHPWord = new PHPWord();
    }
	function Initialize(){
		parent::setDataSchemaForSet();
		parent::setArrayIndex();
		
		$this->isDeleteMergedDoc = true;
		$this->outputAsFileType = $this->fileType["docx"];
		$this->filename = "Export_Word_" . date('Y-m-d_His');
		$this->PHPWord = new PHPWord();
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
	
	function Export(){
		//$print = "";
		$this->filenamePost = $this->filename.".".$this->outputAsFileType;
		$this->docFullExportPath = dirname(__FILE__)."/../temp/".$this->filenamePost;
		
		//$print = $this->filename;
		$document = $this->PHPWord->loadTemplate($this->template);

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

		$document->save($this->docFullExportPath);
		/*
		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		
		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0
		
		ob_clean();
		flush();
		readfile($this->docFullExportPath);
		
		if($this->isDeleteMergedDoc)
			unlink($this->docFullExportPath);
		*/
		
		
		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
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
		readfile($this->docFullExportPath);
		
		if($this->isDeleteMergedDoc)
			unlink($this->docFullExportPath);
		
		// delete 
		//return $this->docFullExportPath;
	}
    
    function __isset($name) {
        return isset($this->_[$name]);
    }
}
?>
