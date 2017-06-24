<?php

function ProcessData($requestData){
    $responseArray = array();
    $processMessageList = ["Processed Result:"];
    $evaluationManager = new EvaluationManager();
    $questionManager = new QuestionManager();
    $questionnaireManager = new QuestionnaireManager();
    $qtnResultManager = new QuestionnaireResultManager();
    $evaProposalManager = new EvaProposalManager();
    
    $submitAction = $requestData->Data->Submit; // "D" or "S"
    $evaProposalID = $requestData->Data->EvaProposalID; //
    $answerList = $requestData->Data->qtnAns;
    
    $questionnaireList = $requestData->Data->questionnaireTable;
    $ratingMarksQtnList = $requestData->Data->ratingMarksQtnList;
    $openEndQtnList = $requestData->Data->openEndQtnList;
    
    $questionnaireID = $questionnaireList[0]->QuestionnaireID;
    $evaluationCode = $questionnaireList[0]->EvaluationCode;
    
    // delete rating question answer
	foreach ($ratingMarksQtnList as $keyIndex => $rowItem) {
        $qtnID = $rowItem->QuestionID;
        $qtnResultManager->Initialize();
        $qtnResultManager->EvaProposalID = $evaProposalID;
        $qtnResultManager->QuestionID = $qtnID;
        
        $qtnResultSelectResponseArray = $qtnResultManager->select();
        
        if($qtnResultSelectResponseArray["affected_rows"]>0){
            foreach($qtnResultSelectResponseArray["data"] as $qtnResultIndex => $qtnRecord){
                $qtnResultManager->Initialize();
                $qtnResultManager->QtnResultID = $qtnRecord["QtnResultID"];
                $qtnResultManager->delete();
            }
        }
    }
    // delete comment question answer
	foreach ($openEndQtnList as $keyIndex => $rowItem) {
        $qtnID = $rowItem->QuestionID;
        $qtnResultManager->Initialize();
        $qtnResultManager->EvaProposalID = $evaProposalID;
        $qtnResultManager->QuestionID = $qtnID;
        
        $qtnResultSelectResponseArray = $qtnResultManager->select();
        
        if($qtnResultSelectResponseArray["affected_rows"]>0){
            foreach($qtnResultSelectResponseArray["data"] as $qtnResultIndex => $qtnRecord){
                $qtnResultManager->Initialize();
                $qtnResultManager->QtnResultID = $qtnRecord["QtnResultID"];
                $qtnResultManager->delete();
            }
        }
    }
    
    // insert rating question
	foreach ($ratingMarksQtnList as $keyIndex => $rowItem) {
        $qtnID = $rowItem->QuestionID;
        $isAnswerExists = false;
        $qtnResultManager->Initialize();
        $answer;
        
        // submitted question answer if found
        foreach($answerList as $answerIndex => $answerRow){
            if($answerIndex == $qtnID){
                $isAnswerExists = true;
//                print_r($answerRow);
                $answer = $answerRow;
                break;
            }
        }
        
        if(!$isAnswerExists)
            continue;
        
        $qtnResultManager->EvaProposalID = $evaProposalID;
        $qtnResultManager->QuestionID = $qtnID;
        
        // find reocrd
        $qtnResultSelectResponseArray = $qtnResultManager->select();
        $isQtnAnsRcdExists = false;
        
        if($qtnResultSelectResponseArray["affected_rows"]>0)
            $isQtnAnsRcdExists = true;
        
        $qtnResultManager->Result = $answer;
        
        if($isQtnAnsRcdExists){
            // update if exist
            $qtnResultManager->update();
        }else{
            // insert if not found
            $qtnResultManager->insert();
        }
    }
    
    // insert comment question
	foreach ($openEndQtnList as $keyIndex => $rowItem) {
        $qtnID = $rowItem->QuestionID;
        $isAnswerExists = false;
        $qtnResultManager->Initialize();
        $answer;
        
        // submitted question answer if found
        foreach($answerList as $answerIndex => $answerRow){
            if($answerIndex == $qtnID){
                $isAnswerExists = true;
                $answer = $answerRow;
                break;
            }
        }
        
        if(!$isAnswerExists)
            continue;
        
        $qtnResultManager->EvaProposalID = $evaProposalID;
        $qtnResultManager->QuestionID = $qtnID;
        
        // find reocrd
        $qtnResultSelectResponseArray = $qtnResultManager->select();
        $isQtnAnsRcdExists = false;
        
        if($qtnResultSelectResponseArray["affected_rows"]>0)
            $isQtnAnsRcdExists = true;
        
        $qtnResultManager->Result = $answer;
        
        if($isQtnAnsRcdExists){
            // update if exist
            $qtnResultManager->update();
        }else{
            // insert if not found
            $qtnResultManager->insert();
        }
    }
    
    // update the evaproposal status
    $evaProposalManager->Initialize();
    $evaProposalManager->EvaProposalID = $evaProposalID;
    $evaProposalManager->EvaProQtnStatusCode = $submitAction;
    $evaProposalManager->update();
    
    $responseArray = Core::CreateResponseArray();
    
    $totalQtnCount = (sizeof($ratingMarksQtnList) + sizeof($openEndQtnList));
    if($submitAction == "D"){
        array_push($processMessageList, "Total $totalQtnCount question answer saved.");
    }else if($submitAction == "S"){
        array_push($processMessageList, "Questionnaire submitted.");
    }
    
    $responseArray['affected_rows'] = $totalQtnCount;
    
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
    
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
    
    array_push($processMessageList, "Questionnarie updated.");
    
    $responseArray = Core::CreateResponseArray();
    
    $responseArray['affected_rows'] = (sizeof($ratingMarksQtnList) + sizeof($openEndQtnList));
    
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
}
?>