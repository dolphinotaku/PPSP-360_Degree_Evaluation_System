<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function ProcessData($requestData){
    $processMessageList = [];
	$staffManager = new StaffManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    $questionManager = new QuestionManager();
    
	$createRows = new stdClass();
    if(!isset($requestData->Data->evaluationCode->EvaluationCode)){
        $responseArray = Core::CreateResponseArray();
        $responseArray['access_status'] = Core::$access_status['Fail'];

        array_push($processMessageList, "Require Evaluation Code.");
        $responseArray['processed_message'] = $processMessageList;
        return $responseArray;
    }
    $evaluationCode = $requestData->Data->evaluationCode->EvaluationCode;
    
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    
    // check is EvaProposalEntry record
    $evaProposalManager->EvaluationCode = $evaluationCode;
    $evaProposalManager->Evaluatee = $userID;
    // find the staffID of the current user
    $staffManager->UserID = $userID;
	$staffResponseArray = $staffManager->select();
    
    if($staffResponseArray["num_rows"] > 0)
        $staffID = $staffResponseArray["data"]["0"]["StaffID"];
    else
        array_push($processMessageList, "Cannot find staff profile by the login user.");
        
    // find the evaProposal in list
    $evaProposalManager->Evaluatee = $staffID;
    $evaProposalManager->EvaluationCode = $evaluationCode;
    $evaProposalResponseArray = $evaProposalManager->select();
    
    // check all evaProposal submitted
    $isAllEvaProposalSubmitted = true;
    if($evaProposalResponseArray["num_rows"] > 0){
        $evaProposalList = $evaProposalResponseArray["data"];
        foreach($evaProposalList as $index => $evaProposalRecord){
            $isAllEvaProposalSubmitted = $isAllEvaProposalSubmitted && $evaProposalRecord["EvaProQtnStatusCode"] == "S";
        }
    }else{
        $isAllEvaProposalSubmitted = false;
    }
    
    if(!$isAllEvaProposalSubmitted){
        array_push($processMessageList, "Some evaluators are not complete the evaluation, cannot generate the report.");
    }
    
    // find question in list
    $questionList = [];
    $questionnaireManager->EvaluationCode = $evaluationCode;
    $qtnnaireResponseArray = $questionnaireManager->select();
    if($qtnnaireResponseArray["num_rows"] > 0){
        $questionManager->QuestionnaireID = $qtnnaireResponseArray["data"]["0"]["QuestionnaireID"];
        $qtnResponseArray = $questionManager->select();
        if($qtnResponseArray["num_rows"] > 0){
            $questionList = $qtnResponseArray["data"];
        }
    }
    
    // classify the question by type
    $qtnRatingMark = [];
    $qtnCommentMark = [];
    foreach($questionList as $index => $questionRecord){
        $type = $questionRecord["Type"];
        if($type == "M"){
            array_push($qtnRatingMark, $questionRecord);
        }else if($type == "O"){
            array_push($qtnCommentMark, $questionRecord);
        }
    }
    
    // if all questionnaire completed, calculate the marks
    if($isAllEvaProposalSubmitted){
    
        $staffQtnResultList = [];
        // calculate the questionnaire mark result
        $qtnResultMarkList = [];
        
        // calculate the supervisor marks
        $supervisorList = CalSupervisorMark($questionList, $evaProposalList);
        
        // calculate the self marks
        $selfList = CalSelfMark($questionList, $evaProposalList);
        
        // calculate the collaborator marks
        $collaboratorList = CalCollaboratorMark($questionList, $evaProposalList);
        
        $qtnResultMarkList["Self"] = $selfList;
        $qtnResultMarkList["Boss"] = $supervisorList;
        $qtnResultMarkList["Collaborator"] = $collaboratorList;
        
        $staffQtnResultList[$staffID] = $qtnResultMarkList;
        
        $objPHPExcel = ReadExcelTemplate();
        $objPHPExcel = MergeEvaluationInfoReport($objPHPExcel, $evaluationCode);
        $objPHPExcel = MergeStaffInfoReport($objPHPExcel, $staffResponseArray);
        $objPHPExcel = MergeChartInExcelReport($objPHPExcel, $qtnRatingMark, $qtnResultMarkList);
        $objPHPExcel = MergeQuestionMarksReport($objPHPExcel, $qtnRatingMark, $qtnResultMarkList);
        $objPHPExcel = MergeQuestionCommentReport($objPHPExcel, $qtnRatingMark, $qtnCommentMark, $qtnResultMarkList);
        
        $exportedReportPath = SaveAndDownload($objPHPExcel, $processMessageList, $evaluationCode, $staffResponseArray);
        
        return ConvertPDFtoByteArray($exportedReportPath, $processMessageList, $evaluationCode, $staffResponseArray);
    
//    $responseArray = Core::CreateResponseArray();
        $responseArray['access_status'] = Core::$access_status['OK'];
    }else{
        $responseArray['access_status'] = Core::$access_status['Fail'];
    }
    
    $responseArray['processed_message'] = $processMessageList;
    return $responseArray;
}

function ReadExcelTemplate(){
    $objReader = PHPExcel_IOFactory::createReader("Excel2007");
    $objReader->setIncludeCharts(TRUE);
    $objPHPExcel = $objReader->load(BASE_TEMPLATE."er01ir.xlsx");
    return $objPHPExcel;
}
function MergeEvaluationInfoReport($objPHPExcel, $evaluationCode){
    $evaluationManager = new EvaluationManager();
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    
    $evaluationManager->EvaluationCode = $evaluationCode;
    
    $evaluationResponseArray = $evaluationManager->select();
    $evaluationDescription = $evaluationResponseArray["data"]["0"]["Description"];
    
    $evaluationCode = strtoupper($evaluationCode);
    // set evaluation code, description
    $objPHPExcel->getActiveSheet()->setCellValueExplicit('C2', $evaluationCode, PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->setCellValueExplicit('C3', $evaluationDescription, PHPExcel_Cell_DataType::TYPE_STRING);
    
    return $objPHPExcel;
}
function MergeStaffInfoReport($objPHPExcel, $staffResponseArray){
	$departmentManager = new DepartmentManager();
	$positionManager = new PositionManager();
    
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    
    $staffID = $staffResponseArray["data"]["0"]["StaffID"];
    $firstName = $staffResponseArray["data"]["0"]["FirstName"];
    $lastName = $staffResponseArray["data"]["0"]["LastName"];
    $departmentCode = $staffResponseArray["data"]["0"]["DepartmentCode"];
    $positionCode = $staffResponseArray["data"]["0"]["PositionCode"];
    
    $departmentManager->DepartmentCode = $departmentCode;
    $positionManager->PositionCode = $positionCode;
    
    $departmentResponseArray = $departmentManager->select();
    $positionResponseArray = $positionManager->select();
    
    if($departmentResponseArray["num_rows"] > 0){
        $departmentCode = $departmentResponseArray["data"]["0"]["Description"];
    }
    
    if($positionResponseArray["num_rows"] > 0){
        $positionCode = $positionResponseArray["data"]["0"]["Description"];
    }
    
    $excelArray = [];
    $excelRowArray = [strtoupper($staffID)];
    array_push($excelArray, $excelRowArray);
    // set staffID to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'C5'
    );
    
    $excelArray = [];
    $excelRowArray = [$firstName." ".$lastName];
    array_push($excelArray, $excelRowArray);
    // set full name to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'C6'
    );
    
    $excelArray = [];
    $excelRowArray = [$departmentCode];
    array_push($excelArray, $excelRowArray);
    // set department to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'C7'
    );
    
    $excelArray = [];
    $excelRowArray = [$positionCode];
    array_push($excelArray, $excelRowArray);
    // set position to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'C8'
    );
    
    return $objPHPExcel;
}
function MergeChartInExcelReport($objPHPExcel, $questionList, $qtnResultList){
    // select "Chart" spreadsheet
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(1);
    
    // prepare the question marks array for chart
    $excelArray = [];
    $excelRowArray = ["", "Boss", "Self", "Collaborator"];
    array_push($excelArray, $excelRowArray);
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionTitle = $qtnRecord["Question"];
        $questionID = $qtnRecord["QuestionID"];
        $bossMark = $qtnResultList["Boss"][$questionID]["Boss"];
        $selfMark = $qtnResultList["Self"][$questionID]["Self"];
        $collaboratorMark = $qtnResultList["Collaborator"][$questionID]["Collaborator"];
        
        $excelRowArray = [];
        // print data table for chart
        array_push($excelRowArray, "Q".($keyIndex+1));
        array_push($excelRowArray, $bossMark);
        array_push($excelRowArray, $selfMark);
        array_push($excelRowArray, $collaboratorMark);
        
        array_push($excelArray, $excelRowArray);
    }
    // set array value to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'A1'
    );
    
    // prepare chart area
    
    //	Set the Labels for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesLabels = array(
        new PHPExcel_Chart_DataSeriesValues('String', 'Chart!$B$1', NULL, 1),
        new PHPExcel_Chart_DataSeriesValues('String', 'Chart!$C$1', NULL, 1),
        new PHPExcel_Chart_DataSeriesValues('String', 'Chart!$D$1', NULL, 1),
    );
    
    $xAxisLength = sizeof($questionList);
    //	Set the X-Axis Labels
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $xAxisTickValues = array(
        new PHPExcel_Chart_DataSeriesValues('String', 'Chart!$A$2:$A$'.($xAxisLength+1), NULL, $xAxisLength),	//	Q.1 to Q.n
    );
    
    //	Set the Data values for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesValues = array(
        new PHPExcel_Chart_DataSeriesValues('Number', 'Chart!$B$2:$B$'.($xAxisLength+1), NULL, $xAxisLength),
        new PHPExcel_Chart_DataSeriesValues('Number', 'Chart!$C$2:$C$'.($xAxisLength+1), NULL, $xAxisLength),
        new PHPExcel_Chart_DataSeriesValues('Number', 'Chart!$D$2:$D$'.($xAxisLength+1), NULL, $xAxisLength),
    );
    
    //	Build the dataseries
    $series = new PHPExcel_Chart_DataSeries(
        PHPExcel_Chart_DataSeries::TYPE_LINECHART,		// plotType
        PHPExcel_Chart_DataSeries::GROUPING_STANDARD,	// plotGrouping
        range(0, count($dataSeriesValues)-1),			// plotOrder
        $dataSeriesLabels,								// plotLabel
        $xAxisTickValues,								// plotCategory
        $dataSeriesValues								// plotValues
    );

    //	Set the series in the plot area
    $plotArea = new PHPExcel_Chart_PlotArea(NULL, array($series));
    //	Set the chart legend
    $legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_BOTTOM, NULL, false);

    $title = new PHPExcel_Chart_Title('360 Degree Feedback');
    $xAxisLabel = new PHPExcel_Chart_Title('Question');
    $yAxisLabel = new PHPExcel_Chart_Title('Rating');
    
    //PHPExcel_Chart_Axis::setAxisOptionsProperties($axis_labels, $horizontal_crosses_value = NULL, $horizontal_crosses = NULL, $axis_orientation = NULL, $major_tmt = NULL, $minor_tmt = NULL, $minimum = NULL, $maximum = NULL, $major_unit = NULL, $minor_unit = NULL)
    $axis =  new PHPExcel_Chart_Axis();
    $axis->setAxisOptionsProperties('nextTo', null, null, null, null, null, 0, 5.5, 1, 0.1);

    //	Create the chart
    $chart = new PHPExcel_Chart(
        'chart360eva',		// name
        $title,			// title
        $legend,		// legend
        $plotArea,		// plotArea
        true,			// plotVisibleOnly
        0,				// displayBlanksAs
        $xAxisLabel,	// xAxisLabel
        $yAxisLabel,		// yAxisLabel
        $axis
    );
    
    //	Set the position where the chart should appear in the worksheet
    $chart->setTopLeftPosition('B25');
    $chart->setBottomRightPosition('M45');

    // select "Worksheet" spreadsheet
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    
    //	Add the chart to the worksheet
    $objWorksheet->addChart($chart);
    
    return $objPHPExcel;
}
function MergeQuestionMarksReport($objPHPExcel, $questionList, $qtnResultList){
    // select "Worksheet" spreadsheet
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    $numOfQuestion = sizeof($questionList);
        
    // prepare the question title array
    $excelArray = [];
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionTitle = $qtnRecord["Question"];
        $questionID = $qtnRecord["QuestionID"];
        
        $excelRowArray = [];
        // print question number
        array_push($excelRowArray, "Q".($keyIndex+1));
        // print question title
        array_push($excelRowArray, $questionTitle);
                
        array_push($excelArray, $excelRowArray);
    }
    
    // insert row fors question table
    $objPHPExcel->getActiveSheet()->insertNewRowBefore(59, $numOfQuestion);
    // merge cell for question title
    for($qtnIndex = 1; $qtnIndex <= $numOfQuestion; $qtnIndex++){
        $objPHPExcel->getActiveSheet()->mergeCells('B'.(58+$qtnIndex).':I'.(58+$qtnIndex));
    }
    
    $objPHPExcel->getActiveSheet()->duplicateStyle($objPHPExcel->getActiveSheet()->getStyle('A58'),'A59:A'.($numOfQuestion-1)); 
    
    // set array value to excel cells
    $objWorksheet->fromArray(
        $excelArray,
        NULL,
        'A59'
    );
        
    // prepare the question marks array
    $excelMarksArray = [];
    $totalBossMarks = 0;
    $totalSelfMarks = 0;
    $totalCollaboratorMarks = 0;
    $totalAverageMarks = 0;
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionID = $qtnRecord["QuestionID"];
        $bossMark = $qtnResultList["Boss"][$questionID]["Boss"];
        $selfMark = $qtnResultList["Self"][$questionID]["Self"];
        $collaboratorMark = $qtnResultList["Collaborator"][$questionID]["Collaborator"];
        $totalMark = $bossMark + $selfMark + $collaboratorMark;
        $averageMark = 0;
        if($totalMark>0)
            $averageMark = $totalMark / 3;
        
        $excelRowArray = [];
        
        $totalBossMarks += $bossMark;
        $totalSelfMarks += $selfMark;
        $totalCollaboratorMarks += $collaboratorMark;
        $totalAverageMarks += $averageMark;
        
        array_push($excelRowArray, $bossMark);
        array_push($excelRowArray, $selfMark);
        array_push($excelRowArray, $collaboratorMark);
        array_push($excelRowArray, $averageMark);
        
        array_push($excelMarksArray, $excelRowArray);
    }
    
    // prepare total marks for boss, self, collaborator
    $excelTotalRowArray = [];
    array_push($excelTotalRowArray, $totalBossMarks);
    array_push($excelTotalRowArray, $totalSelfMarks);
    array_push($excelTotalRowArray, $totalCollaboratorMarks);
    array_push($excelTotalRowArray, $totalAverageMarks);
        
    array_push($excelMarksArray, $excelTotalRowArray);
    
    // prepare total average marks for boss, self, collaborator
    $excelAverageRowArray = [];
    array_push($excelAverageRowArray, ($totalBossMarks / $numOfQuestion));
    array_push($excelAverageRowArray, ($totalSelfMarks / $numOfQuestion));
    array_push($excelAverageRowArray, ($totalCollaboratorMarks / $numOfQuestion));
    array_push($excelAverageRowArray, ($totalAverageMarks / $numOfQuestion));
        
    array_push($excelMarksArray, $excelAverageRowArray);
    
    
    // set array value to excel cells
    $objWorksheet->fromArray(
        $excelMarksArray,
        NULL,
        'J59'
    );
	
	// set height for question title
    for($qtnIndex = 0; $qtnIndex < $numOfQuestion; $qtnIndex++){
		$qtnRecord = $questionList[$qtnIndex];
        $questionTitle = $qtnRecord["Question"];
		$numrows = getRowcountForQuestionTitle($questionTitle);
		// set auto height for question title
		//$objPHPExcel->getActiveSheet()->getRowDimension(58+$qtnIndex)->setRowHeight(-1);
		$objPHPExcel->getActiveSheet()->getRowDimension(58+$qtnIndex+1)->setRowHeight($numrows * 15 + 2.25);
    }
    
    return $objPHPExcel;
}

function MergeQuestionCommentReport($objPHPExcel, $qtnRatingMark, $qtnCommentMark, $qtnResultList){
    $numOfQuestion = sizeof($qtnRatingMark) + sizeof($qtnCommentMark);
    $markResultRowEndAt = 59 + $numOfQuestion + 2;
    
    // select "Worksheet" spreadsheet
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
        
    // prepare the question title array
    $excelArray = [];
    
    $commentRowAt = $markResultRowEndAt;
    $commentAppendRows = 0;
	foreach($qtnCommentMark as $keyIndex => $qtnRecord) {
        $questionTitle = $qtnRecord["Question"];
        $questionID = $qtnRecord["QuestionID"];
        $bossMark = $qtnResultList["Boss"][$questionID]["Boss"];
        $selfMark = $qtnResultList["Self"][$questionID]["Self"];
        $collaboratorMark = $qtnResultList["Collaborator"][$questionID]["Collaborator"];
//        $totalMark = $bossMark + $selfMark + $collaboratorMark;
//        $averageMark = 0;
//        if($totalMark>0)
//            $averageMark = $totalMark / 3;
        
        $excelArray = [];
        $commentRowAt += $commentAppendRows;
        // print question number
//        array_push($excelRowArray, "Q".($keyIndex+1));
        // print question title
//        array_push($excelRowArray, $questionTitle);
        
        array_push($excelArray, []);
        array_push($excelArray, [$questionTitle]);
        array_push($excelArray, []);
        array_push($excelArray, ["Self:"]);
        
        array_push($excelArray, [$selfMark]);
        array_push($excelArray, []);
        array_push($excelArray, ["Evaluators:"]);
        
        array_push($excelArray, [$bossMark]);
        
        array_push($excelArray, [$collaboratorMark]);
        
        $objWorksheet->fromArray(
            $excelArray,
            NULL,
            'A'.$commentRowAt
        );
        
        $styleArray = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 14
        ));
        $objPHPExcel->getActiveSheet()->getStyle('A'.($commentRowAt+1) )->applyFromArray($styleArray);
        
        $styleArray2 = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 12
        ));
        $objPHPExcel->getActiveSheet()->getStyle('A'.($commentRowAt+3) )->applyFromArray($styleArray2);
        $objPHPExcel->getActiveSheet()->getStyle('A'.($commentRowAt+6) )->applyFromArray($styleArray2);
        
        for($qtnIndex = 0; $qtnIndex < sizeof($excelArray); $qtnIndex++){
            $objPHPExcel->getActiveSheet()->mergeCells('A'.($commentRowAt+$qtnIndex).':M'.($commentRowAt+$qtnIndex));
            $objPHPExcel->getActiveSheet()->getStyle('A'.($commentRowAt+$qtnIndex))->getAlignment()->setWrapText(true);
            // Autoheight doesn't work on merged cells.
			// set auto height for question title
            //$objPHPExcel->getActiveSheet()->getRowDimension(($commentRowAt+$qtnIndex))->setRowHeight(-1);
        }
        
        // set height for Question Title
        $numrows = getRowcountForQuestionCommentTitle($questionTitle);
        $objPHPExcel->getActiveSheet()->getRowDimension(($commentRowAt+1))->setRowHeight($numrows * 20 + 2.25);
        
        // set height for self comment
        $numrows = getRowcountForComment($selfMark);
        $objPHPExcel->getActiveSheet()->getRowDimension(($commentRowAt+4))->setRowHeight($numrows * 15 + 2.25);
        $numrows = getRowcountForComment($bossMark);
        $objPHPExcel->getActiveSheet()->getRowDimension(($commentRowAt+7))->setRowHeight($numrows * 15 + 2.25);
        $numrows = getRowcountForComment($collaboratorMark);
        $objPHPExcel->getActiveSheet()->getRowDimension(($commentRowAt+8))->setRowHeight($numrows * 15 + 2.25);
        
        $commentAppendRows = sizeof($excelArray);
    }
    
    return $objPHPExcel;
}

// Autoheight doesn't work on merged cells. It seems this is a problem with Excel not PHPExcel.
// https://stackoverflow.com/questions/13313048/phpexcel-dynamic-row-height-for-merged-cells
function getRowcount($text, $width) {
    $rc = 0;
//    $line = explode("\n", $text);
    $line = explode(PHP_EOL, $text);
    foreach($line as $source) {
        $rc += intval((strlen($source) / $width) +1);
    }
    return $rc;
}
function getRowcountForQuestionTitle($text, $width=82) {
    return getRowcount($text, $width);
}
function getRowcountForQuestionCommentTitle($text, $width=101) {
    return getRowcount($text, $width);
}
function getRowcountForComment($text, $width=138) {
    return getRowcount($text, $width);
}

function SaveAndDownload($objPHPExcel, &$processMessageList, $evaluationCode, $staffResponseArray){
    $exportedReportPath = "";
//    $excelFilePath = str_replace('.php', '.xlsx', __FILE__);
//    $pdfFilePath = str_replace('.php', '.pdf', __FILE__);
//    $encrptyPdfFilePath = str_replace('.php', 'pwd.pdf', __FILE__);
    
    $staffID = $staffResponseArray["data"]["0"]["StaffID"];
    $fileBaseName = $evaluationCode."_".$staffID;
    $fileBaseName = strtoupper($fileBaseName);
    
//    $filenamePost = $fileBaseName.".pdf";
    $excelFilePath = BASE_EXPORT.$fileBaseName.".xlsx";
    $pdfFilePath = BASE_EXPORT.$fileBaseName.".pdf";
    $pdfWithMetaFilePath = BASE_EXPORT.$fileBaseName."meta.pdf";
    $encrptyPdfFilePath = BASE_EXPORT.$fileBaseName."pwd.pdf";
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->setIncludeCharts(TRUE);
    $objWriter->save($excelFilePath);
    $exportedReportPath = $excelFilePath;
    array_push($processMessageList, "Report generation start");
    array_push($processMessageList, "Generate line chart.");
    array_push($processMessageList, "Generate evaluation mark results table.");
    array_push($processMessageList, "Generate evaluation comment result.");
    
    $officeToPDFPath = BASE_3RD."PDF engine/OfficeToPDF.exe";
    $cpdfPath = BASE_3RD."PDF engine/cpdf.exe";
    
    array_push($processMessageList, "Report was generated.");
    
    if(!file_exists($officeToPDFPath)){
        array_push($processMessageList, "$officeToPDFPath not found, avoid to convert excel report to pdf.");
    }
    
    if(file_exists($officeToPDFPath)){
        if(!file_exists($cpdfPath)){
            array_push($processMessageList, "$cpdfPath not found, avoid to encrypt pdf report.");
        }
    }
    
    // return here will down as excel
//    return $exportedReportPath;
    
    // convert the excel to pdf file
    if(file_exists($officeToPDFPath)){
    	if(file_exists($excelFilePath)){
	    //    chdir(__DIR__);
	    //    $cmdOutput = shell_exec("OfficeToPDF.exe /hidden /readonly er01ir.xlsx");
	        chdir(BASE_3RD."PDF engine/");
	        $cmdOutput = shell_exec("OfficeToPDF.exe /hidden /readonly /excel_active_sheet ".$excelFilePath." ".$pdfFilePath);
	//        array_push($processMessageList, "OfficeToPDF.exe /hidden /readonly ".$excelFilePath." ".$pdfFilePath);
	        array_push($processMessageList, "Report was converted as PDF format.");
	            $exportedReportPath = $pdfFilePath;
        }else{
        	array_push($processMessageList, "Excel was not found, cannot convert to pdf file. $excelFilePath");
        }
    }
    
    if(file_exists($officeToPDFPath)){
        if(file_exists($cpdfPath)){
            // set meta data
	    	if(file_exists($pdfFilePath)){
	            chdir(BASE_3RD."PDF engine/");
	            $cmdOutput = shell_exec("cpdf.exe -set-subject \"$evaluationCode\" $pdfFilePath -o ".$pdfWithMetaFilePath);
                
	            array_push($processMessageList, "PDF document information and metadata created.");
	            $exportedReportPath = $pdfWithMetaFilePath;
	        }
	        else{
	        	array_push($processMessageList, "PDF file was not found, cannot add document information and metadata. $pdfFilePath");
	        }
            
            // encrpty the pdf
	    	if(file_exists($pdfWithMetaFilePath)){
	            chdir(BASE_3RD."PDF engine/");
	            $cmdOutput = shell_exec("cpdf.exe -encrypt AES256ISO \"360Evaluation\" \"\" -no-edit ".$pdfWithMetaFilePath." -o ".$encrptyPdfFilePath);
	//            array_push($processMessageList, "cpdf.exe -encrypt AES256ISO \"360Evaluation\" \"\" -no-edit ".$pdfFilePath." -o ".$encrptyPdfFilePath);
	            array_push($processMessageList, "PDF report was encrypted.");
	            $exportedReportPath = $encrptyPdfFilePath;
	        }
	        else{
	        	array_push($processMessageList, "PDF file was not found, cannot encrpty the file. $pdfWithMetaFilePath");
	        }
        }
    }
    
    return $exportedReportPath;
}

function ConvertPDFtoByteArray($exportedReportPath, &$processMessageList, $evaluationCode, $staffResponseArray){
    $responseArray = Core::CreateResponseArray();
    $staffID = $staffResponseArray["data"]["0"]["StaffID"];
    $filename = $evaluationCode."_".$staffID;
    $filename = strtoupper($filename);
    
    if(!file_exists($exportedReportPath)){
        $responseArray['access_status'] = Core::$access_status['Fail'];
        array_push($processMessageList, "$exportedReportPath report not found, nothing download");
    }else{
        $fileAsByteArray = GetFileAsByteArray($exportedReportPath);
        $fileAsString = GetFileAsString($exportedReportPath);
    
        $info = pathinfo($exportedReportPath);
//        $fileBaseName = basename($exportedReportPath,'.'.$info['extension']);

        // return $fileAsByteArray;
//        $responseArray["FileAsByteArray"] = $fileAsByteArray;
//        $responseArray["FileAsByteString"] = $fileAsString;
        $responseArray["FileAsBase64"] = base64_encode(file_get_contents($exportedReportPath));
        $responseArray['access_status'] = Core::$access_status['OK'];
        $responseArray["filename"] = $filename.".".$info['extension'];
    }
    
    $responseArray["processed_message"] = $processMessageList;

    return $responseArray;
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

function GenerateExcelReport($questionList, $qtnResultList){
    $objPHPExcel = new PHPExcel();
    $objWorksheet = $objPHPExcel->getActiveSheet();
    
    $excelArray = [];
    $excelRowArray = ["", "Boss", "Self", "Collaborator"];
    array_push($excelArray, $excelRowArray);
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionTitle = $qtnRecord["Question"];
        $questionID = $qtnRecord["QuestionID"];
        $bossMark = $qtnResultList["Boss"][$questionID]["Boss"];
        $selfMark = $qtnResultList["Self"][$questionID]["Self"];
        $collaboratorMark = $qtnResultList["Collaborator"][$questionID]["Collaborator"];
        
        $excelRowArray = [];
//        array_push($excelRowArray, $questionTitle);
        array_push($excelRowArray, "Q".($keyIndex+1));
        array_push($excelRowArray, $bossMark);
        array_push($excelRowArray, $selfMark);
        array_push($excelRowArray, $collaboratorMark);
        
        array_push($excelArray, $excelRowArray);
    }
    
    $objWorksheet->fromArray(
        $excelArray
    );
    
    //	Set the Labels for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesLabels = array(
        new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$B$1', NULL, 1),	//	2010
        new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$C$1', NULL, 1),	//	2011
        new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$D$1', NULL, 1),	//	2012
    );
    
    $xAxisLength = sizeof($questionList);
    //	Set the X-Axis Labels
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $xAxisTickValues = array(
        new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$2:$A$'.($xAxisLength+1), NULL, $xAxisLength),	//	Q.1 to Q.n
    );
    
    //	Set the Data values for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesValues = array(
        new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$2:$B$'.($xAxisLength+1), NULL, $xAxisLength),
        new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$C$2:$C$'.($xAxisLength+1), NULL, $xAxisLength),
        new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$D$2:$D$'.($xAxisLength+1), NULL, $xAxisLength),
    );
    
    //	Build the dataseries
    $series = new PHPExcel_Chart_DataSeries(
        PHPExcel_Chart_DataSeries::TYPE_LINECHART,		// plotType
        PHPExcel_Chart_DataSeries::GROUPING_STANDARD,	// plotGrouping
        range(0, count($dataSeriesValues)-1),			// plotOrder
        $dataSeriesLabels,								// plotLabel
        $xAxisTickValues,								// plotCategory
        $dataSeriesValues								// plotValues
    );

    //	Set the series in the plot area
    $plotArea = new PHPExcel_Chart_PlotArea(NULL, array($series));
    //	Set the chart legend
    $legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_BOTTOM, NULL, false);

    $title = new PHPExcel_Chart_Title('360 Degree Feedback');
    $xAxisLabel = new PHPExcel_Chart_Title('Question');
    $yAxisLabel = new PHPExcel_Chart_Title('Rating');
    
    $axis =  new PHPExcel_Chart_Axis();
//    $axis->setAxisOptionsProperties('nextTo', null, null, null, 0, 5.5, 1, 0.1);
    $axis->setAxisOptionsProperties('nextTo', null, null, null, null, null, 0, 5.5, 1, 0.1);

    //	Create the chart
    $chart = new PHPExcel_Chart(
        'chart360eva',		// name
        $title,			// title
        $legend,		// legend
        $plotArea,		// plotArea
        true,			// plotVisibleOnly
        0,				// displayBlanksAs
        $xAxisLabel,	// xAxisLabel
        $yAxisLabel,		// yAxisLabel
        $axis
    );
    
    //	Set the position where the chart should appear in the worksheet
    $chart->setTopLeftPosition('A'.($xAxisLength+3));
    $chart->setBottomRightPosition('I'.($xAxisLength+18));

    //	Add the chart to the worksheet
    $objWorksheet->addChart($chart);
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->setIncludeCharts(TRUE);
    $objWriter->save(str_replace('.php', '.xlsx', __FILE__));
    
    return;
}

// return
// Object[
//  QtnID : Mark
// ]
function CalSupervisorMark($questionList, $evaProposalList){
    $qtnMarkForSupervisor = [];
    
	$staffManager = new StaffManager();
    $questionManager = new QuestionManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    
    $qtnResultManager = new QuestionnaireResultManager();
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionID = $qtnRecord["QuestionID"];
        $questionType = $qtnRecord["Type"];
        $qtnMarkForSupervisor[$questionID] = [];
        
        if($questionType == "M")
            $qtnMarkForSupervisor[$questionID]["Boss"] = 0;
        else if ($questionType=="O")
            $qtnMarkForSupervisor[$questionID]["Boss"] = "";
        
        $averageMarkOrComment = [];
        foreach($evaProposalList as $keyIndex => $evaProposalRecord){
            $evaProposalType = $evaProposalRecord["EvaTypeCode"];
            $evaProposalID = $evaProposalRecord["EvaProposalID"];
            
            if($evaProposalType != "B")
                continue;
            
            $qtnResultManager->Initialize();
            $qtnResultManager->EvaProposalID = $evaProposalID;
            $qtnResultManager->QuestionID = $questionID;
            
            $result = null;
            
            // assume must select one record, since evaProposal record marked as submitted
            $qtnResultResponseArray = $qtnResultManager->select();
            if($qtnResultResponseArray["num_rows"] > 0){
                $result = $qtnResultResponseArray["data"]["0"]["Result"];
            }
            
            // convert the question result to numeric or string
            if($questionType == "M"){
                $result = floatval($result);
            }else if ($questionType=="O"){
                // trim the carriage return or space in leading and trailing
                $result = trim(strval($result));
            }
            
            // append the question result to the array list
            array_push($averageMarkOrComment, $result);
        }
        
        // calculate the average mark or merge the comment
        if($questionType == "M"){
            if(sizeof($averageMarkOrComment) > 0)
                $averageMarkOrComment = array_sum($averageMarkOrComment) / sizeof($averageMarkOrComment);
            else
                $averageMarkOrComment = 0;
        }else if ($questionType=="O"){
            $averageMarkOrComment = implode("\r\n", $averageMarkOrComment);
        }
        $qtnMarkForSupervisor[$questionID]["Boss"] = $averageMarkOrComment;
    }
    
    return $qtnMarkForSupervisor;
}
function CalSelfMark($questionList, $evaProposalList){
    $qtnMarkForSelf = [];
    
	$staffManager = new StaffManager();
    $questionManager = new QuestionManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    
    $qtnResultManager = new QuestionnaireResultManager();
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionID = $qtnRecord["QuestionID"];
        $questionType = $qtnRecord["Type"];
        $qtnMarkForSelf[$questionID] = [];
        
        if($questionType == "M")
            $qtnMarkForSelf[$questionID]["Self"] = 0;
        else if ($questionType=="O")
            $qtnMarkForSelf[$questionID]["Self"] = "";
        
        $averageMarkOrComment = [];
        foreach($evaProposalList as $keyIndex => $evaProposalRecord){
            $evaProposalType = $evaProposalRecord["EvaTypeCode"];
            $evaProposalID = $evaProposalRecord["EvaProposalID"];
            
            if($evaProposalType != "S")
                continue;
            
            $qtnResultManager->Initialize();
            $qtnResultManager->EvaProposalID = $evaProposalID;
            $qtnResultManager->QuestionID = $questionID;
            
            $result;
            
            // assume must select one record, since evaProposal record marked as submitted
            $qtnResultResponseArray = $qtnResultManager->select();
            if($qtnResultResponseArray["num_rows"] > 0){
                $result = $qtnResultResponseArray["data"]["0"]["Result"];
            }
            
            // convert the question result to numeric or string
            if($questionType == "M"){
                $result = floatval($result);
            }else if ($questionType=="O"){
                // trim the carriage return or space in leading and trailing
                $result = trim(strval($result));
            }
            
            // append the question result to the array list
            array_push($averageMarkOrComment, $result);
        }
        
        // calculate the average mark or merge the comment
        if($questionType == "M"){
            if(sizeof($averageMarkOrComment) > 0)
                $averageMarkOrComment = array_sum($averageMarkOrComment) / sizeof($averageMarkOrComment);
            else
                $averageMarkOrComment = 0;
        }else if ($questionType=="O"){
            $averageMarkOrComment = implode("\r\n", $averageMarkOrComment);
        }
        $qtnMarkForSelf[$questionID]["Self"] = $averageMarkOrComment;
    }
    
    return $qtnMarkForSelf;
}
function CalCollaboratorMark($questionList, $evaProposalList){
    $qtnMarkForCollaborator = [];
    
	$staffManager = new StaffManager();
    $questionManager = new QuestionManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    
    $qtnResultManager = new QuestionnaireResultManager();
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionID = $qtnRecord["QuestionID"];
        $questionType = $qtnRecord["Type"];
        $qtnMarkForCollaborator[$questionID] = [];
        
        if($questionType == "M")
            $qtnMarkForCollaborator[$questionID]["Collaborator"] = 0;
        else if ($questionType=="O")
            $qtnMarkForCollaborator[$questionID]["Collaborator"] = "";
        
        $averageMarkOrComment = [];
        foreach($evaProposalList as $keyIndex => $evaProposalRecord){
            $evaProposalType = $evaProposalRecord["EvaTypeCode"];
            $evaProposalID = $evaProposalRecord["EvaProposalID"];
            
            if($evaProposalType != "C")
                continue;
            
            $qtnResultManager->Initialize();
            $qtnResultManager->EvaProposalID = $evaProposalID;
            $qtnResultManager->QuestionID = $questionID;
            
            $result;
            
            // assume must select one record, since evaProposal record marked as submitted
            $qtnResultResponseArray = $qtnResultManager->select();
            if($qtnResultResponseArray["num_rows"] > 0){
                $result = $qtnResultResponseArray["data"]["0"]["Result"];
            }
            
            // convert the question result to numeric or string
            if($questionType == "M"){
                $result = floatval($result);
            }else if ($questionType=="O"){
                // trim the carriage return or space in leading and trailing
                $result = trim(strval($result));
            }
            
            // append the question result to the array list
            array_push($averageMarkOrComment, $result);
        }
        
        // calculate the average mark or merge the comment
        if($questionType == "M"){
            if(sizeof($averageMarkOrComment) > 0)
                $averageMarkOrComment = array_sum($averageMarkOrComment) / sizeof($averageMarkOrComment);
            else
                $averageMarkOrComment = 0;
        }else if ($questionType=="O"){
            $averageMarkOrComment = implode("\r\n", $averageMarkOrComment);
        }
        $qtnMarkForCollaborator[$questionID]["Collaborator"] = $averageMarkOrComment;
    }
    
    return $qtnMarkForCollaborator;
}

function CalSupervisorComment($questionList, $evaProposalList){
    $qtnMarkForSupervisor = [];
    
	$staffManager = new StaffManager();
    $questionManager = new QuestionManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    
    $qtnResultManager = new QuestionnaireResultManager();
    
	foreach($questionList as $keyIndex => $qtnRecord) {
        $questionID = $qtnRecord["QuestionID"];
        $questionType = $qtnRecord["Type"];
        $qtnMarkForSupervisor[$questionID] = [];
        
        if($questionType == "M")
            $qtnMarkForSupervisor[$questionID]["Boss"] = 0;
        else if ($questionType=="O")
            $qtnMarkForSupervisor[$questionID]["Boss"] = "";
        
        $averageMarkOrComment = [];
        foreach($evaProposalList as $keyIndex => $evaProposalRecord){
            $evaProposalType = $evaProposalRecord["EvaTypeCode"];
            $evaProposalID = $evaProposalRecord["EvaProposalID"];
            
            if($evaProposalType != "B")
                continue;
            
            $qtnResultManager->Initialize();
            $qtnResultManager->EvaProposalID = $evaProposalID;
            $qtnResultManager->QuestionID = $questionID;
            
            $result = null;
            
            // assume must select one record, since evaProposal record marked as submitted
            $qtnResultResponseArray = $qtnResultManager->select();
            if($qtnResultResponseArray["num_rows"] > 0){
                $result = $qtnResultResponseArray["data"]["0"]["Result"];
            }
            
            // convert the question result to numeric or string
            if($questionType == "M"){
                $result = floatval($result);
            }else if ($questionType=="O"){
                // trim the carriage return or space in leading and trailing
                $result = trim(strval($result));
            }
            
            // append the question result to the array list
            array_push($averageMarkOrComment, $result);
        }
        
        // calculate the average mark or merge the comment
        if($questionType == "M"){
            if(sizeof($averageMarkOrComment) > 0)
                $averageMarkOrComment = array_sum($averageMarkOrComment) / sizeof($averageMarkOrComment);
            else
                $averageMarkOrComment = 0;
        }else if ($questionType=="O"){
            $averageMarkOrComment = implode("\r\n", $averageMarkOrComment);
        }
        $qtnMarkForSupervisor[$questionID]["Boss"] = $averageMarkOrComment;
    }
    
    return $qtnMarkForSupervisor;
}
?>