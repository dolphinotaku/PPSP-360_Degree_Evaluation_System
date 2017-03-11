<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function ProcessData($requestData){
    $processMessageList = ["Processed Message"];
	$staffManager = new StaffManager();
    $evaProposalManager = new EvaProposalManager();
    $questionnaireManager = new QuestionnaireManager();
    $questionManager = new QuestionManager();
    
	$createRows = new stdClass();
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
    
    // require $evaProposalList, $questionList
    // if all questionnaire completed, calculate the marks
    if($isAllEvaProposalSubmitted){
    
        $staffQtnResultList = [];
        // calculate the questionnaire result
        $qtnResultList = [];
        
        // calculate the supervisor marks
        $supervisorList = CalSupervisorMark($questionList, $evaProposalList);
        
        // calculate the self marks
        $selfList = CalSelfMark($questionList, $evaProposalList);
        
        // calculate the collaborator marks
        $collaboratorList = CalCollaboratorMark($questionList, $evaProposalList);
        
        $qtnResultList["Self"] = $selfList;
        $qtnResultList["Boss"] = $supervisorList;
        $qtnResultList["Collaborator"] = $collaboratorList;
        
        $staffQtnResultList[$staffID] = $qtnResultList;
        
        GenerateExcelReport($questionList, $qtnResultList);
    
//    $responseArray = Core::CreateResponseArray();
        $responseArray['access_status'] = Core::$access_status['OK'];
    }else{
        $responseArray['access_status'] = Core::$access_status['Fail'];
    }
    
    $responseArray['processed_message'] = $processMessageList;
    return $responseArray;
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

?>