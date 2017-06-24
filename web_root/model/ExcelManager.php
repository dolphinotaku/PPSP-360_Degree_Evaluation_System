<?php
// require_once 'DatabaseManager.php';
require_once 'Core.php';
require_once '../third-party/PHPExcel_1.8.1/PHPExcel.php';

class ExcelManager {

	/* 
		Construct attributes (table column)
		for insert(), update(), delete()
	*/
    protected $_ = array(
    );
    protected $fileType = array(
    		"xls" => "xls",
    		"xlsx" => "xlsx",
    		"pdf" => "pdf"
    );
    
    public $isTemplate;
	public $outputAsFileType;
	public $filename;
	protected $filenamePost;
	protected $table = "";
	protected $tableList = array();
	protected $excelSheetsHeader = array();

	//public $isExportSequencedColumnOnly = true; // true, export the columns which are defined sequence by user
	protected $processMessageList = array();

	private $customizeExportColumnSequence = array();
	private $skipExportColumnScheme = array();
	private $proposedExportColumnSequence = array();

	private $currentWorkSheetIndex = -1;
    
    function __construct($tableName="") {
    	$this->table = $tableName;
//		parent::__construct();
		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		$this->tableList = array();
    }
	function Initialize(){
//		if(Core::IsNullOrEmptyString($this->table)){
//			$response = Core::CreateResponseArray();
//			$response['access_status'] = $this->access_status['Error'];
//			$response['error'] = $this->sys_err_msg['TableNameNotFound'];
//			return $response;
//		}

		//parent::setDataSchemaForSet();
		//parent::setArrayIndex();
		// Default value
		$this->isTemplate = false;
        
		$this->filename = $this->table."_Export_Excel_" . date('Y-m-d_His');
		$this->outputAsFileType = $this->fileType["xlsx"];
		$this->ClearExportColumnSequence();
		$this->ClearSkipExportColumn();

		$this->excelSheetsHeader = array();
		return true;
	}
	function SetDefaultValue(){
		parent::setDefaultValue();
	}
	
	function setFileName($name){
		$this->filename = $name;
	}

	function AddTable($tableName){
		$isInArray = in_array($tableName, $this->tableList);
		if(!$isInArray)
			array_push($this->tableList, $tableName);
	}

	/* &$objPHPExcel pass by reference */
	function PrepareExportingData(&$objPHPExcel, $currentTableName){
		// Initialise the Excel row number
		$rowCount = 1;
		$column = 'A';
		$columnIndex = 0;

		$this->currentWorkSheetIndex++;

		$worksheetIndex = $this->currentWorkSheetIndex;

		$tableObject = new SimpleTableManager();
		$tableObject->Initialize($currentTableName);

		// Create a new worksheet called "TableName"
		$myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, $currentTableName);
		$objPHPExcel->addSheet($myWorkSheet, $worksheetIndex);
		$objPHPExcel->setActiveSheetIndex($worksheetIndex);
        
        // 'Arial Unicode MS' is a common unicode font style installed with some popular MS product such as MS Office
        // use this font will support to display chinese, japanese in PDf file
//        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial Unicode MS')->setSize(12);
        $objPHPExcel->getDefaultStyle()->getFont()->setName('sun-exta')->setSize(12);
		
		$tableObject->topRightToken = true;
		$resultSet = $tableObject->select();

		// Get proposed export column header sequence
		if(!array_key_exists($currentTableName, $this->proposedExportColumnSequence)){
			$this->proposedExportColumnSequence[$currentTableName] = array();
		}
		$this->proposedExportColumnSequence[$currentTableName] = $this->GetDefaultExportColumnSequence($currentTableName, $tableObject);

		$toBeExportHeaderSequence = $this->proposedExportColumnSequence[$currentTableName];
		// echo json_encode($toBeExportHeaderSequence, JSON_PRETTY_PRINT);

		// Build column header in excel engine
		foreach ($toBeExportHeaderSequence as $key => $headerColumn) {

			//$excelCellCoordinate = $this->getNameFromNumber($columnIndex).$rowCount;
			$excelCellCoordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowCount;
			$excelColumnCoordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).":".PHPExcel_Cell::stringFromColumnIndex($columnIndex);
//			if(parent::IsSystemField($headerColumn))
			if(Core::IsSystemField($headerColumn))
				continue;
			//$objPHPExcel->setActiveSheetIndex(0)->SetCellValue($excelCellCoordinate, $headerColumn);
			$objPHPExcel->getActiveSheet()->SetCellValue($excelCellCoordinate, $headerColumn);

			$tempDataType = Core::SearchDataType($tableObject->dataSchema['data'], 'Field', $headerColumn)[0]['Type'];

			switch (true) {
				case strpos($tempDataType, "char") !== FALSE:
				case strpos($tempDataType, "varchar") !== FALSE:
				case strpos($tempDataType, "text") !== FALSE:
					$objPHPExcel->getActiveSheet()->getStyle($excelColumnCoordinate)
					    ->getNumberFormat()
					    ->setFormatCode('@');
					break;
				case strpos($tempDataType, "tinyint") !== FALSE:
				case strpos($tempDataType, "smallint") !== FALSE:
				case strpos($tempDataType, "mediumint") !== FALSE:
				case strpos($tempDataType, "int") !== FALSE:
				case strpos($tempDataType, "bigint") !== FALSE:
					preg_match_all('!\d+!', $tempDataType, $columnLength);
					if(isset($columnLength[0])){
						$format = str_pad('',$columnLength[0][0],"#");
						$objPHPExcel->getActiveSheet()->getStyle($excelColumnCoordinate)
						    ->getNumberFormat()
						    ->setFormatCode($format);
					}
					break;
				case strpos($tempDataType, "float") !== FALSE:
				case strpos($tempDataType, "double") !== FALSE:
					preg_match_all('!\d+!', $tempDataType, $columnLength);
					// print_r($columnLength[0][0]);
					if(isset($columnLength[0][0]) && isset($columnLength[0][1])){
						$format1 = str_pad('',$columnLength[0][0],"#");
						$format2 = str_pad('',$columnLength[0][1],"0");
						$objPHPExcel->getActiveSheet()->getStyle($excelColumnCoordinate)
						    ->getNumberFormat()
						    ->setFormatCode("$format1.$format2");
					}
					break;
			}


			// Start - format whole column as datetime format if datetime column
			// un like SpreadsheetGear, need to specifically hard code the format and the applied rows.
			// PHPExcel doesn't support column or row styling: you need to set the style for a range of cells
			// http://stackoverflow.com/questions/22090978/phpexcel-how-to-change-data-type-for-whole-column-of-an-excel
			// Worksheet and workbook specifications and limits, excel max. Worksheet size: 1,048,576 rows by 16,384 columns
			/*
			$tempDataType = $this->SearchDataType($tableObject->dataSchema['data'], 'Field', $headerColumn)[0]['Type'];
			$columnIndexInString = PHPExcel_Cell::stringFromColumnIndex($columnIndex); // A/B/C
			switch ($tempDataType) {
				case $tempDataType==="date":
					$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
					->getNumberFormat()
					->setFormatCode('yyyy-m-d');
					break;
				case $tempDataType==="datetime":
				case $tempDataType==="timestamp":
					$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
					->getNumberFormat()
					->setFormatCode('yyyy-m-d hh:mm:ss');
					break;
				case $tempDataType==="time":
					$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
					->getNumberFormat()
					->setFormatCode('hh:mm:ss');
					break;
			}
			*/
			// End - format whole column as datetime format
			 
			$columnIndex++;
		}

		/* Start - set spreadsheet header column */
		// 20150427, keithpoon, export column according to the sequence scheme
		// insert all the column setted in the sequence scheme
		// $tempExportColumnSequence = $this->GetCustomizeExportColumnSequence();
		// $sortedColumnName = $this->GetCustomizeExportColumnSequence();

		// if($tempExportColumnSequence){
		// 	// if table name setted in the sequence scheme
		// 	if(array_key_exists($currentTableName, $tempExportColumnSequence)){
		// 		foreach ($tempExportColumnSequence[$currentTableName] as $indexNum => $sortedColumnName){
		// 			// skip column is Top Priority
		// 			if( $this->IsSkipExportThisColumn($currentTableName, $sortedColumnName))
		// 				continue;

		// 			array_push($this->proposedExportColumnSequence[$currentTableName], $sortedColumnName);

		// 			$excelCellCoordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowCount;
		// 			$objPHPExcel->getActiveSheet()->SetCellValue($excelCellCoordinate, $sortedColumnName);
		// 			$columnIndex++;
		// 		}
		// 	}
		// }


		// 20150123, keithpoon, enhance the column name better
		// if(!$this->isExportSequencedColumnOnly)
		// foreach($tableObject->_ as $headerColumn => $defaultValue){
		// 	// skip column is Top Priority
		// 	if( $this->IsSkipExportThisColumn($currentTableName, $sortedColumnName))
		// 		continue;

		// 	// skip sorted column already inserted at above
		// 	if(array_key_exists($currentTableName, $tempExportColumnSequence)){
		// 		if(in_array($headerColumn, $tempExportColumnSequence[$currentTableName])){
		// 			continue;
		// 		}
		// 	}

		// 	//$excelCellCoordinate = $this->getNameFromNumber($columnIndex).$rowCount;
		// 	$excelCellCoordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowCount;
		// 	if(Core::IsSystemField($headerColumn))
		// 		continue;
		// 	//$objPHPExcel->setActiveSheetIndex(0)->SetCellValue($excelCellCoordinate, $headerColumn);
		// 	$objPHPExcel->getActiveSheet()->SetCellValue($excelCellCoordinate, $headerColumn);
			
		// 	// Start - format whole column as datetime format if datetime column
		// 	// un like SpreadsheetGear, need to specifically hard code the format and the applied rows.
		// 	// PHPExcel doesn't support column or row styling: you need to set the style for a range of cells
		// 	// http://stackoverflow.com/questions/22090978/phpexcel-how-to-change-data-type-for-whole-column-of-an-excel
		// 	// Worksheet and workbook specifications and limits, excel max. Worksheet size: 1,048,576 rows by 16,384 columns
		// 	/*
		// 	$tempDataType = $this->SearchDataType($tableObject->dataSchema['data'], 'Field', $headerColumn)[0]['Type'];
		// 	$columnIndexInString = PHPExcel_Cell::stringFromColumnIndex($columnIndex); // A/B/C
		// 	switch ($tempDataType) {
		// 		case $tempDataType==="date":
		// 			$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
		// 			->getNumberFormat()
		// 			->setFormatCode('yyyy-m-d');
		// 			break;
		// 		case $tempDataType==="datetime":
		// 		case $tempDataType==="timestamp":
		// 			$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
		// 			->getNumberFormat()
		// 			->setFormatCode('yyyy-m-d hh:mm:ss');
		// 			break;
		// 		case $tempDataType==="time":
		// 			$objPHPExcel->getActiveSheet()->getStyle($columnIndexInString.($rowCount+1).":".$columnIndexInString."10000")
		// 			->getNumberFormat()
		// 			->setFormatCode('hh:mm:ss');
		// 			break;
		// 	}
		// 	*/
		// 	// End - format whole column as datetime format
			 
		// 	$columnIndex++;

		// 	// array_push($this->proposedExportColumnSequence[$currentTableName], $headerColumn);
		// }
		/* End - set spreadsheet header column */
		

		/* export worksheet content */
		if(!$this->isTemplate){
			$rowCount = 2;
			// if selectd record count's is not zero
			if(count($resultSet['data'])>0)
				foreach($resultSet['data'] as $rowIndex => $row){
					$columnIndex = 0;
					// loop each column in $row
					// 20150427, keithpoon, export data according to the
					$tempExportedColumnOrder = $this->proposedExportColumnSequence[$currentTableName];

					//foreach($row as $colName => $tempColValue)
					foreach($tempExportedColumnOrder as $key => $colName)
					{
						$tempColValue = $row[$colName];

						if(Core::IsSystemField($colName)) // skip export value when system fields
							continue;
						$excelCellCoordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowCount;

						$tempDataType = Core::SearchDataType($tableObject->dataSchema['data'], 'Field', $colName)[0]['Type'];

								// echo $tempDataType;

						/* if data field is date/time/datetime/timestamp */
						/* format the cell as the related format */
						/* PHPExcel_Style_NumberFormat:http://www.grad.clemson.edu/assets/php/phpexcel/documentation/API/PHPExcel_Style/PHPExcel_Style_NumberFormat.html */
						switch ($tempDataType) {
							case $tempDataType==="date":
								$fitColumn = $this->getNameFromNumber($columnIndex);
								$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
								    ->getNumberFormat()
								    ->setFormatCode('yyyy-m-d');
								break;
							case $tempDataType==="datetime":
							case $tempDataType==="timestamp":
								$fitColumn = $this->getNameFromNumber($columnIndex);
								$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
								    ->getNumberFormat()
								    ->setFormatCode('yyyy-m-d hh:mm:ss');
								break;
							case $tempDataType==="time":
								$fitColumn = $this->getNameFromNumber($columnIndex);
								$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
								    ->getNumberFormat()
								    ->setFormatCode('hh:mm:ss');
								break;
						}

						// switch (true) {
						// 	case strpos($tempDataType, "char") !== FALSE:
						// 	case strpos($tempDataType, "varchar") !== FALSE:
						// 	case strpos($tempDataType, "text") !== FALSE:
						// 	    $objRichText = new PHPExcel_RichText();
						// 		$$tempColValue = $objRichText->createText($tempColValue);
						// 		break;
						// }

						// switch (true) {
						// 	case strpos($tempDataType, "char") !== FALSE:
						// 	case strpos($tempDataType, "varchar") !== FALSE:
						// 	case strpos($tempDataType, "text") !== FALSE:
						// 		$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
						// 		    ->getNumberFormat()
						// 		    ->setFormatCode('@');
						// 		break;
						// 	case strpos($tempDataType, "tinyint") !== FALSE:
						// 	case strpos($tempDataType, "smallint") !== FALSE:
						// 	case strpos($tempDataType, "mediumint") !== FALSE:
						// 	case strpos($tempDataType, "int") !== FALSE:
						// 	case strpos($tempDataType, "bigint") !== FALSE:
						// 		preg_match_all('!\d+!', $tempDataType, $columnLength);
						// 		if(isset($columnLength[0])){
						// 			$format = str_pad('',$columnLength[0][0],"#");
						// 			$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
						// 			    ->getNumberFormat()
						// 			    ->setFormatCode($format);
						// 		}
						// 		break;
						// 	case strpos($tempDataType, "float") !== FALSE:
						// 	case strpos($tempDataType, "double") !== FALSE:
						// 		preg_match_all('!\d+!', $tempDataType, $columnLength);
						// 		// print_r($columnLength[0][0]);
						// 		if(isset($columnLength[0][0]) && isset($columnLength[0][1])){
						// 			$format1 = str_pad('',$columnLength[0][0],"#");
						// 			$format2 = str_pad('',$columnLength[0][1],"0");
						// 			$objPHPExcel->getActiveSheet()->getStyle($excelCellCoordinate)
						// 			    ->getNumberFormat()
						// 			    ->setFormatCode("$format1.$format2");
						// 		}
						// 		break;
						// }


						// if column value is not null and not empty
						if( isset( $tempColValue ) )
							//if( !empty($tempColValue) && $tempColValue != NULL ){
							if( $tempColValue != NULL ){
								
								// 20150706, fixed: leave the cell blank when datetime value is zero.
								$isEmptyDate = false;
								switch ($tempDataType) {
									case $tempDataType==="date":
									case $tempDataType==="datetime":
									case $tempDataType==="timestamp":
									case $tempDataType==="time":
										if(strtotime($tempColValue) == 0 || empty($tempColValue))
											$isEmptyDate = true;
										break;
								}
								
								if(!$isEmptyDate){
								   // echo $tempColValue;
									$objPHPExcel->getActiveSheet()->setCellValue($excelCellCoordinate, $tempColValue);
								}
							}

						$columnIndex++;
					}
					// Increment the Excel row counter
					$rowCount++;
				}
		}
		
		//$objPHPExcel->setActiveSheetIndex(0);
		
		/* set auto column width */
		$fitColumn = "A";
		$fitColumnIndex = 0;
		//while ($fitColumn != $column){
		while ($fitColumnIndex != $columnIndex){
			$fitColumn = $this->getNameFromNumber($fitColumnIndex);
			$objPHPExcel->getActiveSheet()->getColumnDimension($fitColumn)->setAutoSize(true);
			$fitColumnIndex++;
		}
	}

	function SetExportColumnSequence($tableName, $columnName, $index=NULL){
		$exportColumnSequence = $this->customizeExportColumnSequence;

		if($this->IsSystemField($columnName))
			return;

		$insertThisColumn = array($columnName);

		// create table sequence scheme if not found
		if (!array_key_exists($tableName, $exportColumnSequence)){
			array_push($exportColumnSequence, $tableName);
			$exportColumnSequence[$tableName] = array();
		}

		// column sequence index
		if(Core::IsNullOrEmptyString($index))
		{
			$index = count($exportColumnSequence[$tableName]);
		}

		// add column in sequence if not exists
		if (!array_key_exists($columnName, $exportColumnSequence[$tableName])){
			//array_push($exportColumnSequence[$tableName], $columnName);
			array_splice( $exportColumnSequence[$tableName], $index, 0, $insertThisColumn );
		}

		$this->customizeExportColumnSequence = $exportColumnSequence;
	}

	function SetSkipExportColumn($tableName, $skipColumnName){
		$skipExportColumnScheme = $this->skipExportColumnScheme;

		if(Core::IsSystemField($skipColumnName))
			return;

		$skipThisColumn = array($skipColumnName);

		// create table skip scheme if not found
		if (!array_key_exists($tableName, $skipExportColumnScheme)){
			array_push($skipExportColumnScheme, $tableName);
			$skipExportColumnScheme[$tableName] = array();
		}

		// add column in sequence if not exists
		if (!array_key_exists($skipColumnName, $skipExportColumnScheme[$tableName])){
			array_push($skipExportColumnScheme[$tableName], $skipColumnName);
		}

		$this->skipExportColumnScheme = $skipExportColumnScheme;
	}

	function IsSkipExportThisColumn($tableName, $skipColName){
		$isSkipExportThisColumn = false;
		$skipExportColumnScheme = $this->GetSkipExportColumn();

		if(!empty($skipExportColumnScheme))
			if (array_key_exists($tableName, $skipExportColumnScheme)){
				//if (array_key_exists($skipColName, $skipExportColumnScheme[$tableName])){
				if (in_array($skipColName, $skipExportColumnScheme[$tableName])){
					$isSkipExportThisColumn = true;
				}
			}

		// echo "skip col:$skipColName in table: $tableName = ".$isSkipExportThisColumn;

		return $isSkipExportThisColumn;
	}

	function GetCustomizeExportColumnSequence(){
		$exportColSequence = $this->customizeExportColumnSequence;

		// if(count($exportColSequence)==0)
		// 	return false;
		// 	//return array();
		return $exportColSequence;
	}

	// 20161006, keithpoon, move the code to the function
	function GetDefaultExportColumnSequence($tableName, $tableObject){
		$defaultColumnOrder = array();
		$customizeColumnOrder = $this->GetCustomizeExportColumnSequence();

		if($customizeColumnOrder && array_key_exists($tableName, $customizeColumnOrder)){
			foreach ($customizeColumnOrder[$tableName] as $key => $headerColumn) {
				array_push($defaultColumnOrder, $headerColumn);
			}
		}

		foreach($tableObject->_ as $headerColumn => $defaultValue){
			// skip column is Top Priority
			if( $this->IsSkipExportThisColumn($tableName, $headerColumn))
				continue;

			// skip sorted column already inserted at above
			if(array_key_exists($tableName, $customizeColumnOrder)){
				if(in_array($headerColumn, $customizeColumnOrder[$tableName])){
					continue;
				}
			}

			// if(!$this->isExportSequencedColumnOnly){
			array_push($defaultColumnOrder, $headerColumn);
			// }
		}

		// $this->defaultExportColumnOrder = $defaultColumnOrder;

		return $defaultColumnOrder;
	}

	function GetSkipExportColumn(){
		$skipExportColumnScheme = $this->skipExportColumnScheme;

		//if(count($skipExportColumnScheme)==0)
			//return false;
		// 	//return array();
		return $skipExportColumnScheme;
	}

	function ClearExportColumnSequence(){
		$this->customizeExportColumnSequence = array();
		return true;
	}

	function ClearSkipExportColumn(){
		$this->skipExportColumnScheme = array();
		return true;
	}


	function BeforeExportExcel(){
		// $this->GetCustomizeExportColumnSequence();
		// $this->GetSkipExportColumn();

		$this->proposedExportColumnSequence = array();
	}
	
	function Export($directExport = false){
		$this->BeforeExportExcel();
		// Instantiate a new PHPExcel object
		$this->objPHPExcel = new PHPExcel();
		$objPHPExcel = new PHPExcel();
		// Set the active Excel worksheet to sheet 0
		//$objPHPExcel->setActiveSheetIndex(0);

		$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
		->setLastModifiedBy("Maarten Balliauw")
		->setTitle("Office 2007 XLSX Test Document")
		->setSubject("Office 2007 XLSX Test Document")
		->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Test result file");
		
		$objPHPExcel->removeSheetByIndex(0);

		if(count($this->tableList)>0){
			//foreach($this->tableList as $key => $tableName) {
			foreach($this->tableList as $tableName) {
				$this->PrepareExportingData($objPHPExcel, $tableName);
			}
		}

		// assign the output file type
		if(Core::IsNullOrEmptyString($this->outputAsFileType)){
			//$this->outputAsFileType = $this->fileType["xlsx"];
			$this->outputAsFileType = "xlsx";
		}else if(!array_key_exists($this->outputAsFileType, $this->fileType)){
			$this->outputAsFileType = "pdf";
		}

		// assign the filename
		if(Core::IsNullOrEmptyString($this->filename))
		if(count($this->tableList) == 1)
			$this->filename = $this->tableList[0];
		else
			$this->filename = basename($_SERVER['PHP_SELF']);

		$this->filenamePost = $this->filename.".".date('Ymd_His').".".$this->outputAsFileType;

		// $exportFilename = $this->filename.".".date('Ymd_His').".".$this->outputAsFileType;
		$exportedPath = BASE_EXPORT.$this->filenamePost;

		// echo $this->filenamePost;

		// Change these values to select the Rendering library that you wish to use and its directory location on your server

		// $rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
		// $rendererLibrary = 'PDF engine/dompdf/dompdf-0.6.2/';
		// local the dompdf enginer folder, PHPExcel 1.8.0+ not support dompdf 0.7.0+
		//$rendererLibrary = 'PDF engine/dompdf/dompdf-0.7.0/';

		// extremely slow performance
		// $rendererName = PHPExcel_Settings::PDF_RENDERER_TCPDF;
		// $rendererLibrary = 'PDF engine/TCPDF/TCPDF-6.2.13/';

		$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
		$rendererLibrary = 'PDF engine/MPDF/mpdf-6.1.3/';
		
		$rendererLibraryPath = BASE_3RD . $rendererLibrary;

		//$rendererLibraryPath = $this->base_Path["serverHost"].$this->base_Path["thrid-party"].$rendererLibrary;

		//echo "<br>$rendererLibraryPath<br>";
		//echo "<br>".dirname(__FILE__)."<br>";
				
		switch($this->outputAsFileType){
			case "xlsx":
				// // Redirect output to a client¡¦s web browser (Excel2007)
				// header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				// header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				// header('Cache-Control: max-age=0');
				// // If you're serving to IE 9, then the following may be needed
				// header('Cache-Control: max-age=1');
				
				// // If you're serving to IE over SSL, then the following may be needed
				// header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				// header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
				// header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				// header ('Pragma: public'); // HTTP/1.0
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
				//$objWriter->save('php://output');
				$objWriter->save($exportedPath);
				break;
			case "xls":
				// // Redirect output to a client¡¦s web browser (Excel5)
				// header('Content-Type: application/vnd.ms-excel');
				// header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				// header('Cache-Control: max-age=0');
				// // If you're serving to IE 9, then the following may be needed
				// header('Cache-Control: max-age=1');
				
				// // If you're serving to IE over SSL, then the following may be needed
				// header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				// header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
				// header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				// header ('Pragma: public'); // HTTP/1.0
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
				// $objWriter->save('php://output');
				$objWriter->save($exportedPath);
				break;
			case "pdf":

				if (!PHPExcel_Settings::setPdfRenderer(
						$rendererName,
						$rendererLibraryPath
				)) {
					die(
							'NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
							'<br />' .
							'at the top of this script as appropriate for your directory structure'
					);
				}
				
				
				// // Redirect output to a client¡¦s web browser (PDF)
				// header('Content-Type: application/pdf');
				// header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				// header('Cache-Control: max-age=0');
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
				// $objWriter->save('php://output');
				$objWriter->save($exportedPath);
				break;
		}

		$fileAsByteArray = $this->GetFileAsByteArray($exportedPath);
		$fileAsString = $this->GetFileAsString($exportedPath);

		// return $fileAsByteArray;
		$responseArray = Core::CreateResponseArray();
		$responseArray["FileAsByteArray"] = $fileAsByteArray;
		$responseArray["FileAsByteString"] = $fileAsString;
		$responseArray["FileAsBase64"] = base64_encode(file_get_contents($exportedPath));
		$responseArray['access_status'] = Core::$access_status['OK'];
		$responseArray["filename"] = $this->filename.".".$this->outputAsFileType;

		return $responseArray;
	}

	function DirectExport(){
		$this->BeforeExportExcel();
		// Instantiate a new PHPExcel object
		$this->objPHPExcel = new PHPExcel();
		$objPHPExcel = new PHPExcel();
		// Set the active Excel worksheet to sheet 0
		//$objPHPExcel->setActiveSheetIndex(0);

		$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
		->setLastModifiedBy("Maarten Balliauw")
		->setTitle("Office 2007 XLSX Test Document")
		->setSubject("Office 2007 XLSX Test Document")
		->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Test result file");
		
		$objPHPExcel->removeSheetByIndex(0);

		if(count($this->tableList)>0){
			//foreach($this->tableList as $key => $tableName) {
			foreach($this->tableList as $tableName) {
				$this->PrepareExportingData($objPHPExcel, $tableName);
			}
		}

		// assign the output file type
		if(Core::IsNullOrEmptyString($this->outputAsFileType)){
			//$this->outputAsFileType = $this->fileType["xlsx"];
			$this->outputAsFileType = "xlsx";
		}else if(!array_key_exists($this->outputAsFileType, $this->fileType)){
			$this->outputAsFileType = "pdf";
		}

		// assign the filename
		if(Core::IsNullOrEmptyString($this->filename))
            if(count($this->tableList) == 1)
                $this->filename = $this->tableList[0];
            else
                $this->filename = basename($_SERVER['PHP_SELF']);

        $this->setFileName($this->tableList[0]);
		$this->filenamePost = $this->filename.".".date('Ymd_His').".".$this->outputAsFileType;
		$exportedPath = BASE_EXPORT.$this->filenamePost;

		// Change these values to select the Rendering library that you wish to use and its directory location on your server

		// $rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
		// $rendererLibrary = 'dompdf/dompdf-0.6.2/';
		// local the dompdf enginer folder, PHPExcel 1.8.0+ not support dompdf 0.7.0+
		//$rendererLibrary = 'dompdf/dompdf-0.7.0/';

		// extremely slow performance
		// $rendererName = PHPExcel_Settings::PDF_RENDERER_TCPDF;
		// $rendererLibrary = 'TCPDF/TCPDF-6.2.13/';

		$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
		$rendererLibrary = 'PDF engine/MPDF/mpdf-6.1.3/';
		
		$rendererLibraryPath = BASE_3RD . $rendererLibrary;
		
		//$rendererLibraryPath = $this->base_Path["serverHost"].$this->base_Path["thrid-party"].$rendererLibrary;

		//echo "<br>$rendererLibraryPath<br>";
		//echo "<br>".dirname(__FILE__)."<br>";

		// return;
				
		switch($this->outputAsFileType){
			case "xlsx":
				// Redirect output to a client¡¦s web browser (Excel2007)
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				header('Cache-Control: max-age=0');
				// If you're serving to IE 9, then the following may be needed
				header('Cache-Control: max-age=1');
				
				// If you're serving to IE over SSL, then the following may be needed
				header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
				header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				header ('Pragma: public'); // HTTP/1.0
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
				$objWriter->save('php://output');
				// $objWriter->save($exportedPath);
				break;
			case "xls":
				// Redirect output to a client¡¦s web browser (Excel5)
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				header('Cache-Control: max-age=0');
				// If you're serving to IE 9, then the following may be needed
				header('Cache-Control: max-age=1');
				
				// If you're serving to IE over SSL, then the following may be needed
				header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
				header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				header ('Pragma: public'); // HTTP/1.0
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
				$objWriter->save('php://output');
				// $objWriter->save($exportedPath);
				break;
			case "pdf":

				if (!PHPExcel_Settings::setPdfRenderer(
						$rendererName,
						$rendererLibraryPath
				)) {
					die(
							'NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
							'<br />' .
							'at the top of this script as appropriate for your directory structure'
					);
				}
				
				
				// Redirect output to a client¡¦s web browser (PDF)
				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment;filename="'.$this->filenamePost.'"');
				header('Cache-Control: max-age=0');
				
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
				$objWriter->save('php://output');
				// $objWriter->save($exportedPath);
				break;
		}

	}

	function GetFileAsByteArray($filename){
		// $byteArray = unpack("N*", file_get_contents($filename));
		$handle = fopen($filename, "rb");
		$fsize = filesize($filename);
		$contents = fread($handle, $fsize);

		// 20160927, keithpoon, i don't kown why the array index start from 1
		$byteArray = unpack("N*",$contents);
		$newByteArray = array();

		$arrayIndex = 0;
		foreach ($byteArray as $key => $value){
			$newByteArray[$arrayIndex] = $value;
			$arrayIndex++;
		}
		
		return $newByteArray;
	}

	function GetFileAsString($filename){
		// $byteArray = unpack("N*", file_get_contents($filename));
		$handle = fopen($filename, "rb");
		$fsize = filesize($filename);
		$contents = fread($handle, $fsize);
		$byteArray = unpack("N*",$contents);

		$string = "";
		foreach ($byteArray as $key => $value)
		{ 
			$string = $string.$value;
		    //echo $byteArray[$n];
		}
		
		return $string;
	}


	function Import($uploadedExcelPath, $importType = IMPORTTYPE_INSERTANDUPDATE)
	{
		$isInsertAndUpdate = $importType == IMPORTTYPE_INSERTANDUPDATE;
		$isInsert = $importType == IMPORTTYPE_INSERT;
		$isUpdate = $importType == IMPORTTYPE_UPDATE;
		$this->excelSheetsHeader = array();
		
		$dataSet = array();

		// Convert worksheet to array
		foreach($this->tableList as $tableName) {
			$dataSet[$tableName] = $this->ConvertWorksheet2Array($uploadedExcelPath, $tableName);
		}
		// Import worksheet
		foreach($this->tableList as $tableName) {
			$tempProcessMessage = "Import $tableName";
			array_push($this->processMessageList, $tempProcessMessage);
			$dataTable = $dataSet[$tableName]['excelData'];
			$this->ImportData($dataTable, $tableName);
		}

		//return json_encode($dataSet, JSON_PRETTY_PRINT);
		//return json_encode($this->processMessageList, JSON_PRETTY_PRINT);

		$responseArray = Core::CreateResponseArray();
		// $this->responseArray['importResult'] = $this->processMessageList;

//		$responseArray = $this->GetResponseArray();
		$responseArray['processed_message'] = $this->processMessageList;
		$responseArray['access_status'] = Core::$access_status['OK'];

		return $responseArray;
		// return $this->processMessageList;
	}
	
	function ImportData($dataTable, $tableName){
		for($tableRowIndex=0; $tableRowIndex < count($dataTable); $tableRowIndex++){
			$addThisRow = $dataTable[$tableRowIndex];
			$dataTable[$tableRowIndex] = $this->AmendDataRowBeforeGoToImport($tableName, $tableRowIndex, $addThisRow);
		}

		// print_r($dataTable);
		// return;
		$this->ImportInsertOrUpdateData($dataTable, $tableName);
	}
	
	function ImportInsertOrUpdateData($insertCurDataTable, $tableName){
		for($tableRowIndex = 0; $tableRowIndex < count($insertCurDataTable); $tableRowIndex++){
			$_tableManager = new SimpleTableManager();
			$_tableManager->Initialize($tableName);
			
			$importThisRow = $insertCurDataTable[$tableRowIndex];
			
			$importThisRow = $this->UpdateDataRowBeforeImport($tableName, $tableRowIndex, $importThisRow);
			$_tableManager->setArrayIndex();
			foreach($importThisRow as $columnName => $cellValue) {
				// 20161019, keithpoon, 0 should not seem as null or empty in some real case
				// if(!empty($cellValue))

				// 20161113, keithpoon, try to set NULL to datetime field if it is null
				// if(is_null($cellValue))
				// 	$_tableManager->$columnName = NULL;
				// else
					$_tableManager->$columnName = $cellValue;
			}

			// print_r($_tableManager->_);
			// continue;
			
			$this->CustomImportInsertOrUpdateData($_tableManager, $tableRowIndex);
			
			$_tableManager->close();
		}
	}
	
	function AmendDataRowBeforeGoToImport($tableName, $excelRowIndex, $addThisRow){
		//print_r($addThisRow);
		return $addThisRow;
	}
	
	function UpdateDataRowBeforeImport($tableName, $excelRowIndex, $importThisRow){
		return $importThisRow;
	}
	
	function CustomImportInsertOrUpdateData($tableObject, $rowIndex){
		$isPKMissing = true;
		$primaryKeySchema = $tableObject->getPrimaryKeyName();
		$keyString = "";

		foreach ($primaryKeySchema['data']['Field'] as $index => $value){
			if(Core::IsNullOrEmptyString($tableObject->_[$value])){
				$isPKMissing = $isPKMissing && true;
				//break;
			}else{
				$keyString = $keyString . $tableObject->_[$value].", ";
				$isPKMissing = false;
			}
		}
		$keyString = trim($keyString, ", ");
		
		$excelRowIndex = $rowIndex + 1;
		$responseArray = array();
		$tempProcessMessage = "";
		
		$tableObject->topRightToken = true;
		$tableObject->debug = true;

		// Check key exists
		$isKeyExists = $tableObject->CheckKeyExists();
		// Update if exists, insert if not exists
		if($isKeyExists)
			$responseArray = $tableObject->update(true);
		else
			$responseArray = $tableObject->insert();
        
//        print_r($responseArray);

		if(!$isKeyExists)
		{
			if($responseArray['affected_rows'] > 0){
				$tempProcessMessage = "Rows ".$excelRowIndex." "."inserted: ".$keyString;
			}else{
				$tempProcessMessage = "Rows ".$excelRowIndex." insert ".$responseArray['access_status'].": ".$responseArray['error'];
			}
		}else{
			if($responseArray['affected_rows'] > 0){
				$tempProcessMessage = "Rows ".$excelRowIndex." "."updated: ".$keyString;
			}
			// sql query result is success but no rows updated.
			// because the imported data total same as the record in database, no records will be changes.
			else if($responseArray["access_status"] == Core::$access_status["OK"]){
				$tempProcessMessage = "Rows ".$excelRowIndex." sql query sccuess but no record updated.";	
			}
			else{
				$tempProcessMessage = "Rows ".$excelRowIndex." update ".$responseArray['access_status'].": ".$responseArray['error'];
			}
		}
		
		array_push($this->processMessageList, $tempProcessMessage);
	}
	

	function CreateArrayFromTable($tableName)
	{

	}
	
	/**
	 * return excel array which contains
	 * 
	 *  excel header name, row data records, data schema
	 */
	function ConvertWorksheet2Array($readExcelPath, $tableName, $readStartFromRow=2){
		$worksheetInfo = array();
		$excelArray = array();
// 		$excelArray['Indexed'] = array();
// 		$excelArray['Associative'] = array();
		$rowIndex = 0;

		$objReader = PHPExcel_IOFactory::createReaderForFile($readExcelPath);
		$objReader->setReadDataOnly(true);
		$objReader->setLoadSheetsOnly( array($tableName) );

		$objPHPExcel = new PHPExcel();
		$objPHPExcel = $objReader->load($readExcelPath);

		$objWorksheet = $objPHPExcel->getActiveSheet();

		$_noOfUsedRows = $objWorksheet->getHighestRow();
		$_noOfUsedColsInString = $objWorksheet->getHighestColumn();
		$_noOfUsedCols = PHPExcel_Cell::columnIndexFromString($_noOfUsedColsInString);

		$_tableManager = new SimpleTableManager();
		$_tableManager->Initialize($tableName);
		
		$worksheetInfo['dataSchema'] = $_tableManager->dataSchema['data'];
		$worksheetInfo['excelScannedRow'] = $_noOfUsedRows - ($readStartFromRow -1);
		$worksheetInfo['excelRow'] = $rowIndex;
		$worksheetInfo['excelCol'] = $_noOfUsedCols;
		
		// store the excel header to this object
		$this->excelSheetsHeader[$tableName] = array();
		for($i=0; $i<$_noOfUsedCols; $i++){
			$excelColHeaderText = $objWorksheet->getCellByColumnAndRow($i, 1)->getValue();
			array_push($this->excelSheetsHeader[$tableName], $excelColHeaderText);
		}

		$skipExcelRowCounter = $readStartFromRow;

		// convert the excel to array
		foreach ($objWorksheet->getRowIterator() as $row) {
			if($skipExcelRowCounter > 1){
				$skipExcelRowCounter--;
				continue;
			}
			$excelArray[$rowIndex] = array();

			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			// This loops all cells,
			// even if it is not set.
			// By default, only cells
			// that are set will be
			// iterated.

			$isWholeRowEmpty = true;
			$colIndex = 0;
			foreach ($cellIterator as $cell) {
				$headerName = $this->excelSheetsHeader[$tableName][$colIndex];
				$columnFound = $this->MappingSheetColumnsWithDataTable($_tableManager, $headerName);
				// echo "column $headerName found result (bool)".$columnFound;

				if((bool)$columnFound){
					// 20161019, keithpoon, 0 should not seem as null or empty in some real case
					// if(is_null($cell->getValue()) || empty($cell->getValue())){
					$cellValue = $cell->getValue();
					if(is_null($cellValue) || $cellValue == "")
					{
						$isWholeRowEmpty = $isWholeRowEmpty && true;
						$excelArray[$rowIndex][$headerName] = $cellValue;
						$colIndex++;
						continue;
					}else{
						$isWholeRowEmpty = false;
					}
					
					// extract the Data Type
					$columnInfo = $_tableManager->getColumnInfo($headerName);

					if(strpos($columnInfo['Type'], '(') !== false)
						$columnInfo['Type'] = substr($columnInfo['Type'], 0, strpos($columnInfo['Type'], '('));
					
					$format = "Y-m-d H:i:s";
					
					$type = $columnInfo['Type'];
					
					if($columnInfo){
						switch($type){
							case "datetime":
							case "timestamp":
								$dateTimeValue = $cell->getFormattedValue();
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, date_default_timezone_get()));
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, 'Asia/Hong_Kong'));
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, 'UTC'));
								// echo "cellValue:$cellValue, dateTimeValue:$dateTimeValue";
								$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue));
								// echo "cellValue2:$cellValue";

								
								$tempDateObj = new DateTime();
								$tempDateObj = date_create_from_format($format, $cellValue);
								
								$tempDateObj->sub(new DateInterval('PT8H'));
								
								$cellValue = $tempDateObj->format($format);
								break;
							case "date":
								$format = "Y-m-d";
								$dateTimeValue = $cell->getFormattedValue();
								$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue));
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, date_default_timezone_get()));
								
								$tempDateObj = new DateTime();
								$tempDateObj = date_create_from_format($format, $cellValue);
								$tempDateObj->sub(new DateInterval('PT8H'));
								
								$cellValue = $tempDateObj->format($format);
								break;
							case "time":
								$format = "H:i:s";
								$dateTimeValue = $cell->getFormattedValue();
								$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue));
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, date_default_timezone_get()));
								//$cellValue = date($format, PHPExcel_Shared_Date::ExcelToPHP($dateTimeValue, true, 'Asia/Hong_Kong'));
								
								$tempDateObj = new DateTime();
								$tempDateObj = date_create_from_format($format, $cellValue);
								$tempDateObj->sub(new DateInterval('PT8H'));
								
								$cellValue = $tempDateObj->format($format);
								break;
							case "tinyint":
								break;
							case "smallint":
								break;
							case "mediumint":
								break;
							case "int":
								break;
							case "bigint":
								break;
							
							case "float":
								break;
							case "double":
								break;
							case "decimal":
								break;

							case "varchar":
								break;
							case "char":
								break;
							case "text":
								break;
							default:
								$cellValue = $cell->getValue();
								break;
						}
					}else{
						echo "column not found";
					}
					
					$excelArray[$rowIndex][$headerName] = $cellValue;
					
					// 20150701, Not very useful
					//$excelArray[$headerName][$rowIndex] = $cell->getValue();
					
				}
				$colIndex++;
			}
			if($isWholeRowEmpty){
				unset($excelArray[$rowIndex]);
				continue;
			}
			$rowIndex++;
		}

		$worksheetInfo['excelRow'] = $rowIndex;
		$worksheetInfo['excelHeader'] = $this->excelSheetsHeader[$tableName];
		$worksheetInfo['excelData'] = $excelArray;
		
		$_tableManager->close();
		
		return $worksheetInfo;
	}
	
	/**
	 * map sheet column with table schema
	 * return a int position
	 */
	function MappingSheetColumnsWithDataTable($tableObject, $columnName){
		//return true;
		return $this->DefaultMappingSheetColumnsWithDataTable($tableObject, $columnName);
	}

	private function DefaultMappingSheetColumnsWithDataTable($tableObject, $columnName, $ignoreNotFoundColumn = true){
		if(!$ignoreNotFoundColumn)
			return true;
		$activeSheetCells = array();
		$rowIndex = 0;

		//return true;
		// i don't know why IsColumnExists return empty =.=
		//return $tableObject->IsColumnExists($columnName);
		
		// this also doesn't work
 		//if (array_key_exists($columnName, $tableObject->_))
 		//	return array_search($columnName, $tableObject->_);
				
		$columnIndex=0;
		foreach($tableObject->_ as $key => $value){
			if($key==$columnName)
				break;
			$columnIndex++;
		}
		
		if($columnIndex >=0)
			return true;
		
		//return $columnIndex;
	}
	/*
	private function DefaultMappingSheetColumnsWithDataTable($tableName, $excelWorkSheets, $ignoreNotFoundColumn = true){
		$activeSheetCells = array();
		$rowIndex = 0;

		$tableObject = new SimpleTableManager();
		$tableObject->Initialize($tableName);
		
		// convert the excel to array
		foreach ($excelWorkSheets->getRowIterator() as $row) {
			if($skipExcelRowCounter > 1){
				$skipExcelRowCounter--;
				continue;
			}
			$activeSheetCells[$rowIndex] = array();
		
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
			// even if it is not set.
			// By default, only cells
			// that are set will be
			// iterated.
				
			$colIndex = 0;
			foreach ($cellIterator as $cell) {
				$headerName = $this->excelSheetsHeader[$tableName][$colIndex];
				if($tableObject->IsColumnExists($headerName) || !$ignoreNotFoundColumn){
					$activeSheetCells[$rowIndex][$headerName] = $cell->getValue();
					$activeSheetCells[$headerName][$rowIndex] = $cell->getValue();
				}
				$colIndex++;
			}
			$rowIndex++;
		}
		return $activeSheetCells;
	}*/

    function __isset($name) {
        return isset($this->_[$name]);
    }
	
	// http://stackoverflow.com/questions/181596/how-to-convert-a-column-number-eg-127-into-an-excel-column-eg-aa
	/*
	function GetExcelColumnName($columnNumber)
	{
		$dividend = $columnNumber;
		$columnName = "";
		$modulo;

		while ($dividend > 0)
		{
			$modulo = ($dividend - 1) % 26;
			$columnName = chr(65 + $modulo) + $columnName;
			$dividend = (($dividend - $modulo) / 26);
		} 

		return $columnName;
	}
	*/
	//http://stackoverflow.com/questions/3302857/algorithm-to-get-the-excel-like-column-name-of-a-number
	function getNameFromNumber($num) {
		$numeric = $num % 26;
		$letter = chr(65 + $numeric);
		$num2 = intval($num / 26);
		if ($num2 > 0) {
			return getNameFromNumber($num2 - 1) . $letter;
		} else {
			return $letter;
		}
	}
}
?>
