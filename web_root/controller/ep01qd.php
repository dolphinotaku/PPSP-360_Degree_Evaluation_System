<?php

function ProcessData($requestData){
    $responseArray = array();
    $processMessageList = ["Processed Result:"];
    $evaluationManager = new EvaluationManager();
    $questionManager = new QuestionManager();
    $questionnaireManager = new QuestionnaireManager();
    
    $questionnaireList = $requestData->Data->questionnaireTable;
    $ratingMarksQtnList = $requestData->Data->ratingMarksQtnList;
    $openEndQtnList = $requestData->Data->openEndQtnList;
    
    $questionnaireID = $questionnaireList[0]->QuestionnaireID;
    $evaluationCode = $questionnaireList[0]->EvaluationCode;
    
    // find the questionnaire ID
//    $questionnaireManager->EvaluationCode = $evaluationCode;
//    $questionnaireResponseArray = $questionnaireManager->select();
//    $questionnaireList = $questionnaireResponseArray["data"];
//    $questionnaireID = $questionnaireList[0]["QuestionnaireID"];
    
    // update questionnaire description
	foreach ($questionnaireList as $keyIndex => $rowItem) {
        $questionnaireManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$questionnaireManager->$columnName = $value;
		}
        $questionnaireManager->QuestionnaireID = $questionnaireID;
		$responseArray = $questionnaireManager->update();
	}
    
    // delete all questions
    $questionManager->QuestionnaireID = $questionnaireID;
    $questionResponseArray = $questionManager->select();
    $questionList = $questionResponseArray["data"];
	foreach ($questionList as $keyIndex => $rowItem) {
        $questionManager->Initialize();
        
        $questionManager->QuestionID = $rowItem["QuestionID"];
        $deleteResult = $questionManager->delete();
	}
    
//    return Core::CreateResponseArray();
    
    // insert all rating marks questions
	foreach ($ratingMarksQtnList as $keyIndex => $rowItem) {
        $questionManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$questionManager->$columnName = $value;
		}
        $questionTitle = trim($questionManager->Question);
//        print_r($questionTitle);
//        print_r(gettype($questionManager->Qusetion));
        if(Core::IsNullOrEmptyString($questionTitle)){
            continue;
        }
        $questionManager->Question = $questionTitle;
        
        $questionManager->QuestionID = null;
        $questionManager->QuestionnaireID = $questionnaireID;
        $questionManager->Type = "M";
		$responseArray = $questionManager->insert();
        
	}
    // insert all open end questions
	foreach ($openEndQtnList as $keyIndex => $rowItem) {
        $questionManager->Initialize();
		foreach ($rowItem as $columnName => $value) {
			$questionManager->$columnName = $value;
		}
        
        $questionTitle = trim($questionManager->Question);
        if(Core::IsNullOrEmptyString($questionTitle)){
            continue;
        }
        $questionManager->Question = $questionTitle;
        
        $questionManager->QuestionID = null;
        $questionManager->QuestionnaireID = $questionnaireID;
        $questionManager->Type = "O";
		$responseArray = $questionManager->insert();
	}
    
    array_push($processMessageList, "Questionnaire updated.");
    
    $responseArray = Core::CreateResponseArray();
    $responseArray['affected_rows'] = (sizeof($ratingMarksQtnList) + sizeof($openEndQtnList));
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
}
?>