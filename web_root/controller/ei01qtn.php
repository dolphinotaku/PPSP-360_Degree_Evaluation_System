<?php

function ProcessData($requestData){
    $responseArray = Core::CreateResponseArray();
    $processMessageList = [];
    $evaluationManager = new EvaluationManager();
    $questionManager = new QuestionManager();
    $questionnaireManager = new QuestionnaireManager();
    
    if(isset($requestData->Data->evaluationCode->EvaluationCode)){
        $evaluationCode = $requestData->Data->evaluationCode->EvaluationCode;
    }else{
        array_push($processMessageList, "Evaluation Code required.");
        $responseArray['processed_message'] = $processMessageList;
        return $responseArray;
    }
    
    $evaluationManager->EvaluationCode = $evaluationCode;
    $evaluationResponseArray = $evaluationManager->select();
    
    // if evaluation code found
    if(!$evaluationResponseArray["affected_rows"] > 0){
        array_push($processMessageList, "Evaluation Code not found");
        $responseArray['processed_message'] = $processMessageList;
        return $responseArray;
    }
    
    $questionnaireManager->EvaluationCode = $evaluationCode;
    $qtnnaireResponseArray = $questionnaireManager->select();
    
    // if questionnaire found
    if($qtnnaireResponseArray["affected_rows"] > 0){
        
    }else{
        $qtnnaireResponseArray = $questionnaireManager->insert();
    }
    
    $qtnnaireList = $qtnnaireResponseArray["data"];
    
    $questionManager->QuestionID = null;
    $questionManager->QuestionnaireID = $qtnnaireList[0]["QuestionnaireID"];
    $questionResponseArray = $questionManager->select();
    
    $responseArray['evaluationTable'] = $evaluationResponseArray["data"];
    $responseArray['questionnaireTable'] = $qtnnaireResponseArray["data"];
    $responseArray['questionTable'] = $questionResponseArray["data"];
    
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
}

?>